# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Projektgedächtnis (Session-Notizen, Entscheidungen, Deploy-Weg): `docs/history/`.

**Diese Datei aktuell halten.** Wer Stack, Ablauf, Kommandos oder Konventionen ändert, passt CLAUDE.md im selben Commit an. Veraltete Angaben hier sind schlimmer als keine.

## Was prevju ist

Statische HTML-Entwürfe hochladen, Link an den Kunden schicken, optional mit Passwort. Ein Admin (Inertia + React) verwaltet "Sites", jede Site ist ein Ordner mit Dateien und wird unter `/s/<slug>/` ausgeliefert. Mehr gibt es nicht. Neue Features nur, wenn sie diesen Zweck direkt stützen.

## Tech Stack

- PHP ^8.3, Laravel ^13.17, Inertia v3 + React 19 + TypeScript (Admin unter `/sites`, Login `/login`)
- SQLite (eine Datei), Session/Cache als Files, Queue `sync`. Keine Worker, keine Cronjobs, kein Redis.
- Uploads liegen auf der Disk `sites` (`storage/app/sites/<slug>/`, siehe `config/filesystems.php`)
- Vite + Tailwind 4, shadcn-Komponenten (angepasst, `resources/js/components/ui/`). Design-Richtung und Tokens: **`DESIGN.md` lesen, bevor UI angefasst wird.** Die Kunden-Passwortseite ist Blade (`site-password`) mit denselben Tokens aus `resources/css/app.css`
- Tests: PHPUnit ^12.5, Formatierung: Laravel Pint
- Betrieb: Docker-Image auf Basis `serversideup/php:8.4-fpm-nginx`, Host-Port 7738 (im Container 8080), Daten im Volume `prevju-data`. HTTPS macht ein vorgeschalteter Reverse-Proxy.

## Kommandos

```bash
composer install && npm install && php artisan migrate
ADMIN_EMAIL=a@b.de ADMIN_PASSWORD=secret php artisan app:ensure-admin
php artisan serve                         # http://localhost:8000/sites
npm run dev                               # Vite, parallel zu serve
npx tsc -p .                              # Typecheck (läuft auch in CI)
php artisan test                          # alle Tests
php artisan test --filter=test_password_protected_site   # einzelner Test
vendor/bin/pint                           # Code formatieren
php artisan app:ensure-admin              # Admin-User aus ADMIN_EMAIL / ADMIN_PASSWORD anlegen
```

Docker (Self-Hosting mit fertigem Image `baeroe/prevju` von Docker Hub):

```bash
cp .env.docker.example .env               # APP_URL, ADMIN_EMAIL, ADMIN_PASSWORD setzen
docker compose up -d                      # zieht baeroe/prevju:latest
docker build -t baeroe/prevju:latest .    # lokal bauen statt ziehen
```

Beim Container-Start läuft `docker/entrypoint.d/99-prevju.sh`: fehlt `APP_KEY`, wird einer erzeugt und in `storage/app/.app-key` (Volume) gespeichert; dann SQLite anlegen, migrieren, Admin-User sicherstellen, `optimize` (cacht die Config inkl. Key).

Release: Git-Tag `vX.Y.Z` pushen → `.github/workflows/docker.yml` baut amd64+arm64 und pusht `baeroe/prevju:X.Y.Z`, `:X.Y` und `:latest`, danach legt es ein GitHub-Release mit generierten Notes an (aus PRs/Commits seit dem letzten Tag). Hinweise für Nutzer (Breaking Changes, Upgrade-Schritte) danach im Release von Hand ergänzen. Braucht Repo-Secrets `DOCKERHUB_USERNAME` und `DOCKERHUB_TOKEN`.

## Architektur

Drei Dateien tragen die gesamte Logik:

- `app/Models/Site.php`: Model plus Dateiverwaltung. Die Disk ist die einzige Wahrheit über Dateien (keine `files`-Spalte). `addFile()` speichert einen Upload unter relativem Pfad; ZIPs werden stattdessen entpackt (ein einzelner Wrapper-Ordner wird entfernt, `__MACOSX` übersprungen). `cleanPath()` weist `..`, `.` und leere Segmente ab (Path-Traversal). `deleting` löscht den ganzen Ordner. Slug wird im `creating`-Event zufällig erzeugt (10 Zeichen).
- `app/Http/Controllers/SiteController.php`: liefert Dateien aus (`show`) und schaltet passwortgeschützte Sites frei (`unlock`). Path-Traversal wird per `realpath`-Vergleich mit dem Site-Root geblockt. Ordner ohne `index.html` liefern die erste `.html`. SPA-Fallback: unbekannte Pfade ohne Dateiendung liefern die `index.html` der Site (Deep-Links in SPAs), fehlende Assets mit Endung bleiben 404. Was für Nutzer geht und was nicht, steht als Tabelle in der README ("What works"). Freischaltung ist ein Session-Flag `site.<id>`.
- `app/Http/Controllers/Admin/SiteController.php`: Admin-CRUD als Inertia-Seiten (`resources/js/pages/sites/index|show.tsx`). Upload ist **eine Datei pro Request** (`POST /sites/{id}/files`, JSON 204), damit der Client Fortschritt zeigt und `post_max_size` nie greift. Passwort wird gehasht, nie an den Client gegeben; leer lassen behält es, `clear_password` entfernt es. Eingeloggte Admins öffnen passwortgeschützte Sites ohne Passwort.
- Frontend (`resources/js/`): `lib/upload.ts` (XHR mit Fortschritt, Ordner-Drop rekursiv, Wrapper-Ordner beim ersten Upload abschneiden), `lib/delete-with-undo.ts` (Löschen wird 5 s verzögert, Toast mit „Rückgängig“), `components/site-preview.tsx` (skaliertes iframe als Vorschau, `sandbox="allow-scripts"` ohne same-origin, damit Entwurfs-Skripte nicht an Admin-Seite und Session kommen; lädt deshalb über `/p/<signatur>/<slug>/`, weil die Sandbox keine Cookies mitschickt. Signatur = HMAC(slug + Passwort-Hash, APP_KEY), rotiert mit dem Passwort), `components/crop-frame.tsx` (Schneidmarken).

Routen (`routes/web.php`): `/` leitet auf `/sites`, `/login` (guest), `/sites…` (auth), `POST /s/{slug}` = unlock, `GET /s/{slug}/{path?}` = show mit Wildcard-Pfad, `GET /p/{signature}/{slug}/{path?}` = passwortfreie Admin-Vorschau.

## Konventionen und Fallstricke

- Upload-Limit 100 MB pro Datei: Validierung in `Admin\SiteController::upload` (`max:102400`) und Hinweistext in `components/dropzone.tsx`. Dockerfile erlaubt 200M (`PHP_UPLOAD_MAX_FILE_SIZE`, `NGINX_CLIENT_MAX_BODY_SIZE`), muss über dem App-Limit bleiben. Beim Ändern alle drei prüfen.
- Docker-Build hat eine Node-Stage (`--platform=$BUILDPLATFORM`, Assets nur einmal nativ gebaut) und kopiert `public/build` ins PHP-Image.
- Tests laufen ohne Vite-Build (`withoutVite()` im `TestCase`); Inertia-Tests prüfen, dass die Page-Datei existiert.
- Tests in `tests/Feature/SiteTest.php` schreiben in das echte `storage/app/sites/abc123` und räumen es im `tearDown` auf. Kein Storage-Fake.
- UI-Texte im Admin und in der Passwort-View sind Deutsch.
- Trusted Proxies (`bootstrap/app.php`): nur private Netze, damit `X-Forwarded-Proto` vom Reverse-Proxy (NPM/Traefik/Caddy im Docker-Netz) zu `https://`-URLs führt. Nicht auf `*` stellen: der Container-Port ist oft öffentlich, dann wäre `X-Forwarded-For` fälschbar und die Login-Drosselung umgehbar.
- `.env` wird im Docker-Setup nicht ins Image kopiert; alle Laufzeitwerte kommen aus `docker-compose.yml` bzw. der `.env` daneben.
