# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Projektgedächtnis (Session-Notizen, Entscheidungen, Deploy-Weg): `docs/history/`.

**Diese Datei aktuell halten.** Wer Stack, Ablauf, Kommandos oder Konventionen ändert, passt CLAUDE.md im selben Commit an. Veraltete Angaben hier sind schlimmer als keine.

**Die Doku (prevju.dev, `docs/`) ist Teil jeder Änderung.** Jede Änderung, die Nutzer oder Betreiber betrifft (Verhalten, Setup, Umgebungsvariablen, Routen, MCP-Tools, UI-Abläufe, Kompatibilität), passt im selben PR die betroffenen Doku-Seiten an, **auf Englisch und Deutsch**. Kein Merge mit veralteter Doku. Reine Refactorings ohne sichtbare Auswirkung brauchen keine Doku.

## Was prevju ist

Statische HTML-Entwürfe hochladen, Link an den Kunden schicken, optional mit Passwort. Ein Admin (Inertia + React) verwaltet "Sites", jede Site ist ein Ordner mit Dateien und wird unter `/s/<slug>/` ausgeliefert. Sites lassen sich zu Projekten bündeln: ein Link `/project/<slug>` zeigt alle Sites des Projekts, neueste zuerst, ein Passwort öffnet alle. Mehr gibt es nicht. Neue Features nur, wenn sie diesen Zweck direkt stützen.

## Tech Stack

- PHP ^8.3, Laravel ^13.17, Inertia v3 + React 19 + TypeScript (Admin unter `/en/sites` bzw. `/de/sites`, Login `/en/login`)
- SQLite (eine Datei), Session/Cache als Files, Queue `sync`. Keine Worker, keine Cronjobs, kein Redis.
- Uploads liegen auf der Disk `sites` (`storage/app/sites/<slug>/`, siehe `config/filesystems.php`)
- Vite + Tailwind 4, shadcn-Komponenten (angepasst, `resources/js/components/ui/`). Design-Richtung und Tokens: **`DESIGN.md` lesen, bevor UI angefasst wird.** Die Kunden-Passwortseite ist Blade (`site-password`) mit denselben Tokens aus `resources/css/app.css`
- Tests: PHPUnit ^12.5, Formatierung: Laravel Pint
- Betrieb: Docker-Image auf Basis `serversideup/php:8.4-fpm-nginx`, Host-Port 7738 (im Container 8080), Daten im Volume `prevju-data`. HTTPS macht ein vorgeschalteter Reverse-Proxy.

## Kommandos

```bash
composer install && npm install && php artisan migrate
ADMIN_EMAIL=a@b.de ADMIN_PASSWORD=secret php artisan app:ensure-admin
php artisan serve                         # http://localhost:8000/en/sites
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

Release: Git-Tag `vX.Y.Z` pushen → `.github/workflows/docker.yml` baut amd64+arm64 und pusht `baeroe/prevju:X.Y.Z`, `:X.Y` und `:latest`, danach legt es ein GitHub-Release mit generierten Notes an (aus PRs/Commits seit dem letzten Tag). Hinweise für Nutzer (Breaking Changes, Upgrade-Schritte) danach im Release von Hand ergänzen. **Jedes Release bekommt vor dem Taggen einen Abschnitt in `docs/upgrading.md` und `docs/de/upgrading.md`** (was sich ändert, was beim Update zu tun ist, oder ausdrücklich „nichts zu tun“). Release Notes auf GitHub und Doku sagen dasselbe. Braucht Repo-Secrets `DOCKERHUB_USERNAME` und `DOCKERHUB_TOKEN` (Scope Read/Write/Delete, weil `.github/workflows/dockerhub.yml` damit bei jeder README-Änderung auf `main` die Beschreibung auf Docker Hub synchronisiert).

## Architektur

Kernlogik:

- `app/Models/Site.php`: Model plus Dateiverwaltung. Die Disk ist die einzige Wahrheit über Dateien (keine `files`-Spalte). `addFile()` speichert einen Upload unter relativem Pfad; ZIPs werden stattdessen entpackt (ein einzelner Wrapper-Ordner wird entfernt, `__MACOSX` übersprungen). `cleanPath()` weist `..`, `.` und leere Segmente ab (Path-Traversal). `deleting` löscht den ganzen Ordner. Slug wird im `creating`-Event zufällig erzeugt (10 Zeichen). `isOpenFor()` ist die einzige Zugriffsregel: kein Passwort, eingeloggter Admin, Session `site.<id>` oder Projekt mit Passwort und Session `project.<id>`. `$touches = ['project']`: jeder Upload schiebt auch das Projekt nach oben. `adminCard()` = `summary()` + signierte `preview_url` (nur Admin, die URL umgeht das Passwort).
- `app/Http/Controllers/SiteController.php`: liefert Dateien aus (`show`) und schaltet passwortgeschützte Sites frei (`unlock`). Path-Traversal wird per `realpath`-Vergleich mit dem Site-Root geblockt. Ordner ohne `index.html` liefern die erste `.html`. SPA-Fallback: unbekannte Pfade ohne Dateiendung liefern die `index.html` der Site (Deep-Links in SPAs), fehlende Assets mit Endung bleiben 404. Was für Nutzer geht und was nicht, steht in `docs/en/what-works.md`. Freischaltung ist ein Session-Flag `site.<id>`. Die Passwortseite `site-password` ist generisch (`$name`, `$action`, `$intro`, `$button`) und dient auch für Projekte.
- `app/Http/Controllers/Admin/SiteController.php`: Admin-CRUD als Inertia-Seiten (`resources/js/pages/sites/index|show.tsx`). Upload ist **eine Datei pro Request** (`POST /sites/{id}/files`, JSON 204), damit der Client Fortschritt zeigt und `post_max_size` nie greift. Passwort wird gehasht, nie an den Client gegeben; leer lassen behält es, `clear_password` entfernt es. Eingeloggte Admins öffnen passwortgeschützte Sites ohne Passwort.
- `app/Models/Project.php` + `app/Http/Controllers/ProjectController.php`: Projekt = Name, Slug, optionales Passwort; `sites.project_id` nullable (`nullOnDelete`: Projekt löschen lässt die Sites stehen). `sites()` ist nach `updated_at` absteigend sortiert. Die Kundenseite (`pages/projects/public.tsx`, Inertia ohne Admin-Layout) bekommt `preview_url` nur für Sites, die der Betrachter laut `Site::isOpenFor()` öffnen darf, sonst `null`. Admin: `Admin\ProjectController` (`pages/projects/show.tsx`), Zuordnung über `project_id` beim Anlegen/Ändern einer Site. In der Übersicht blättert `components/project-preview.tsx` durch die Sites (höchstens 3 iframes gemountet, Pause bei Hover/Fokus, steht bei reduzierter Bewegung).
- MCP (`app/Mcp/`, Route in `routes/ai.php`): `POST /mcp` mit `laravel/mcp`, Auth per Sanctum-Bearer-Token (`auth:sanctum`). Tokens verwaltet der Admin unter `/tokens`. 12 Tools in `app/Mcp/Tools/`, Namen in kebab-case aus dem Klassennamen (`write-files`, `get-upload-url`, …). Wer in Beschreibungen oder den Server-Instructions ein Tool nennt, muss diesen Namen verwenden (Test `test_tool_names_mentioned_in_texts_exist`). Binärdateien gehen nie durch MCP-Argumente: `get-upload-url` liefert eine signierte URL (`POST /upload/{site}`, 15 min, CSRF-frei) für `curl -F file=@…`. Fehler als `ValidationException` werfen, nur die kommen als lesbare Meldung beim Modell an. `get-compatibility` liest zur Laufzeit `docs/en/what-works.md` (dieselbe Seite wie prevju.dev/en/what-works, eine Quelle für Menschen und Agenten). Die Datei muss im Docker-Image landen, `docs/` also nicht komplett in `.dockerignore` aufnehmen. Ein Test prüft Überschrift und Tabelle. Server-Instructions und die Upload-Tools verweisen auf `get-compatibility`.
- `replace` (Upload und `write-files`): `Site::writeInto()` schreibt in ein Staging-Verzeichnis `<slug>.new-*` und tauscht danach um. Die Live-Site ist nie leer oder halb ersetzt, ein Fehler ändert nichts.
- Frontend (`resources/js/`): `lib/upload.ts` (XHR mit Fortschritt, Ordner-Drop rekursiv, Wrapper-Ordner beim ersten Upload abschneiden), `lib/delete-with-undo.ts` (Löschen wird 5 s verzögert, Toast mit „Rückgängig“), `components/site-preview.tsx` (skaliertes iframe als Vorschau, `sandbox="allow-scripts"` ohne same-origin, damit Entwurfs-Skripte nicht an Admin-Seite und Session kommen; lädt deshalb über `/p/<signatur>/<slug>/`, weil die Sandbox keine Cookies mitschickt. Signatur = HMAC(slug + Passwort-Hash, APP_KEY), rotiert mit dem Passwort), `components/crop-frame.tsx` (Schneidmarken).

Routen (`routes/web.php`): Admin unter Sprachpräfix `/{locale}` (`en|de`): `/{locale}/login` (guest), `/{locale}/sites…`, `/{locale}/projects…`, `/{locale}/tokens` (auth). `/` und alte Admin-Pfade ohne Präfix (`/sites/3`, `/login`) leiten auf die zuletzt genutzte Sprache um (Cookie `locale`, sonst `en`). `GET /locale/{lang}` = Sprachumschalter der Kundenseiten (setzt das Cookie, leitet nur innerhalb der App zurück), `POST /s/{slug}` = unlock, `GET /project/{slug}` = Kundenseite eines Projekts, `POST /project/{slug}` = Projekt-Unlock (beide Unlocks mit `throttle:10,1,unlock`, eigener Prefix, damit sie sich das Limit nicht mit `/login` teilen), `GET /s/{slug}/{path?}` = show mit Wildcard-Pfad, `GET /p/{signature}/{slug}/{path?}` = passwortfreie Admin-Vorschau, `/tokens` (auth) = MCP-Tokens, `POST /upload/{site}` (signiert) = Upload für MCP-Clients, `POST /mcp` (Sanctum) = MCP-Server.

## Doku (prevju.dev)

VitePress in `docs/` mit eigenem `docs/package.json` (nicht im App-Build, nicht im Image). `cd docs && npm install && npm run dev`. Seiten: `index.md` (Landingpage), `setup`, `reverse-proxy`, `uploading`, `projects`, `what-works`, `mcp`, `upgrading`. Theme mit den prevju-Tokens in `docs/.vitepress/theme/style.css`. Zweisprachig: Englisch unter `docs/en/` (VitePress-Locale `root` mit `link: '/en/'`), Deutsch unter `docs/de/`, gleiche Dateinamen, damit der Sprachumschalter passt. Pfade ohne Sprachpräfix (alte Links wie `/setup`, auch `/`) leitet `docs/.vitepress/theme/index.ts` im Browser auf `/en/…` um; das greift auch auf der 404-Seite von GitHub Pages. Interne Links immer mit Präfix (`/en/setup`, `/de/setup`). Jede Seite gibt es in beiden Sprachen, und die Kompatibilitätstabellen müssen dieselben Urteile haben (`tests/Feature/DocsTest.php`). Impressum und Datenschutz: die deutsche Fassung (`docs/de/imprint.md`, `docs/de/privacy.md`) ist verbindlich, die englische ist Übersetzung. `llms.txt` enthält nur die englischen Doku-Seiten (`llmstxt({ workDir: 'en' })`, legt Markdown-Kopien im Root ab). `docs/history/` ist Projektgedächtnis und per `srcExclude` von der Website ausgenommen. `vitepress-plugin-llms` erzeugt `llms.txt` und `llms-full.txt`. `docs/package.json` hat `overrides: { vite: ^6.4.3 }`: VitePress 1.x pinnt Vite 5 mit bekannten Dev-Server-Lücken (Dependabot); Build und Dev-Server laufen mit Vite 6 geprüft. Entfernen, sobald VitePress 2 stabil ist. Hosting: GitHub Pages über `.github/workflows/docs.yml` (baut bei Push auf `main`, wenn sich `docs/` ändert), Custom Domain `prevju.dev` in den Repo-Settings unter Pages. Wer Setup, Tools oder Verhalten ändert, passt die passende Doku-Seite im selben PR an; die README ist nur noch Kurzfassung mit Links. Screenshots liegen pro Sprache in `docs/public/screenshots/{en,de}/`, englische Seiten zeigen die englischen, deutsche die deutschen. Sie stammen aus Demo-Daten, nie aus echten Sites.

## Konventionen und Fallstricke

- Upload-Limit 100 MB pro Datei: Validierung in `Admin\SiteController::upload` (`max:102400`) und Hinweistext in `components/dropzone.tsx`. Dockerfile erlaubt 200M (`PHP_UPLOAD_MAX_FILE_SIZE`, `NGINX_CLIENT_MAX_BODY_SIZE`), muss über dem App-Limit bleiben. Beim Ändern alle drei prüfen.
- Docker-Build hat eine Node-Stage (`--platform=$BUILDPLATFORM`, Assets nur einmal nativ gebaut) und kopiert `public/build` ins PHP-Image.
- Tests laufen ohne Vite-Build (`withoutVite()` im `TestCase`); Inertia-Tests prüfen, dass die Page-Datei existiert.
- Tests in `tests/Feature/SiteTest.php` schreiben in das echte `storage/app/sites/abc123` und räumen es im `tearDown` auf. Kein Storage-Fake.
- UI ist zweisprachig (en/de), Default Englisch. Texte nur über Schlüssel: `lang/en.json` und `lang/de.json` (flach, Punkt-Namensräume wie `sites.new`, Platzhalter `:name`). PHP `__('key')`, React `t('key')`/`tn('key', n)` (Plural `.one`/`.other`) aus `resources/js/lib/i18n.ts`, Admin-URLs im Frontend immer über `path('/sites')`. Die Übersetzungen kommen als Inertia-Shared-Prop (`HandleInertiaRequests`), `SetLocale` setzt die Sprache aus dem URL-Präfix (Admin) bzw. dem Cookie (Kundenseiten). `tests/Feature/LocaleTest.php` prüft, dass beide Dateien dieselben Schlüssel haben und jeder im Code genutzte Schlüssel existiert. Neue Texte: Schlüssel in beiden Dateien anlegen. MCP-Fehlermeldungen sind fest Englisch (der Endpoint läuft ohne `SetLocale`).
- Trusted Proxies (`bootstrap/app.php`): nur private Netze, damit `X-Forwarded-Proto` vom Reverse-Proxy (NPM/Traefik/Caddy im Docker-Netz) zu `https://`-URLs führt. Nicht auf `*` stellen: der Container-Port ist oft öffentlich, dann wäre `X-Forwarded-For` fälschbar und die Login-Drosselung umgehbar.
- `.env` wird im Docker-Setup nicht ins Image kopiert; alle Laufzeitwerte kommen aus `docker-compose.yml` bzw. der `.env` daneben.
