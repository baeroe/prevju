---
tags: [session]
datum: 2026-09-28
---
# 2026-09-28 – Docker-Release, eigenes UI, MCP und Doku-Seite

Session vom 2026-09-25 bis 2026-09-28. Vorgänger: [[2026-09-11 Erstes Deploy und CLAUDE.md]]; dessen rsync-Deploy ist überholt, siehe unten.

## Kontext
prevju lief funktional mit Filament-Admin und einem Dockerfile, aber ohne veröffentlichtes Image, ohne GitHub-Repo und ohne Doku. Ziel: selbst hostbar per `docker compose` wie n8n oder Vito, eigener Look, Agenten-Anbindung, öffentliche Doku für Nutzer und Portfolio.

## Was wurde gemacht
- **Distribution:** Repo `baeroe/prevju` (public), Image `baeroe/prevju` auf Docker Hub für amd64+arm64 (`f8bdeba`). Tag `vX.Y.Z` → Build, Push, GitHub-Release mit generierten Notes (`1e12780`). Releases v0.1.0 bis v0.3.1.
- **Betrieb:** `APP_KEY` wird beim ersten Start erzeugt und im Volume gespeichert. Host-Port 7738 (`d78bda9`). Trusted Proxies für private Netze (`e53eb97`).
- **Repo-Regeln:** Rulesets für `main` (PR, 1 Approval, Tests grün, Admin-Bypass) und für `v*`-Tags (nicht löschen/verschieben). Test-Workflow mit Typecheck (`940d0f6`).
- **UI:** Filament ersetzt durch Inertia v3 + React 19 + angepasstes shadcn, Richtung „Proof Sheet“ in `DESIGN.md` (`96968f4`). Cards mit Live-Vorschau, Drag & Drop für Ordner/ZIPs, Undo beim Löschen, SPA-Fallback.
- **MCP:** `laravel/mcp` + Sanctum-Tokens, 10 Tools, Token-Seite mit Setup-Befehl für Claude Code und Codex (`9fd5334`, `d482943`).
- **Doku:** VitePress in `docs/` auf GitHub Pages unter https://prevju.dev, Englisch + Deutsch, Impressum/Datenschutz, `llms.txt` (`7a6171a`, `beab645`, `729f950`). README gekürzt, Docker-Hub-Beschreibung synchronisiert per Workflow (`b61fc52`). GitHub-About mit Beschreibung, Website, Topics.
- **Lizenz:** MIT (`LICENSE`).

## Entscheidungen & Warum
- **Docker Hub statt GHCR:** Nutzerwunsch, dort suchen Self-Hoster zuerst.
- **Eigenes UI statt Filament:** Nutzer wollte einen unverwechselbaren Look. Cards mit Vorschau als iframe der echten Site statt Screenshot-Dienst, damit der Container schlank bleibt.
- **Vorschau-iframes in Sandbox** (`allow-scripts`, ohne same-origin): Sonst liefe Entwurfs-JS beim Öffnen der Übersicht mit Admin-Session. Weil die Sandbox keine Cookies schickt, laden Vorschauen über `/p/<hmac>/<slug>/`, Signatur rotiert mit dem Passwort.
- **Upload eine Datei pro Request:** Fortschritt pro Datei, `post_max_size` greift nie.
- **`replace` per Staging-Verzeichnis und Swap:** Live-Site nie leer oder halb ersetzt, kaputte ZIP ändert nichts. Eigenes `clear-files` bleibt, ist aber als Weg für neue Versionen ausdrücklich nicht gedacht.
- **Binärdateien nie durch MCP-Argumente:** MCP-Aufrufe sind JSON, Base64-ZIPs würden Millionen Tokens kosten. Stattdessen signierte Upload-URL für `curl -F`. Textdateien direkt über `write-files`.
- **Nur Token-Auth, kein OAuth:** reicht für Claude Code und Codex. claude.ai/Desktop-Connectors bräuchten OAuth (Passport), bewusst verschoben.
- **Keine Subdomain pro Site:** hätte absolute Pfade und SPAs sauber gelöst, braucht aber Wildcard-DNS und -Zertifikat, widerspricht „einfaches Setup“. Stattdessen SPA-Fallback und Kompatibilitätstabelle.
- **Eine Quelle für die Kompatibilität:** `docs/what-works.md` wird auf der Website gezeigt und von `get-compatibility` zur Laufzeit gelesen. Deutsche Fassung per Test synchron gehalten.
- **Doku im selben Repo (Option A)** wie bei Nginx Proxy Manager: Code und Doku ändern sich im selben PR. VitePress statt Laravel-Doku-Tools (Jigsaw, HydePHP): ausgereifter, `llms.txt`, Nutzer sieht den Stack nicht.
- **GitHub Pages statt Cloudflare/All-Inkl:** Repo ist public, also kostenlos, keine Server-Zugangsdaten im Repo.
- **MIT statt AGPL:** Ziel ist Verbreitung und Portfolio, kein Geschäftsmodell. Abhängigkeiten sind ebenfalls MIT.
- **Doku ist Teil jeder Änderung:** in CLAUDE.md festgeschrieben, inklusive Upgrading-Notiz vor jedem Release-Tag.

## Stolpersteine
- **Live-Server hat sich geändert:** prevju.hauss.dev läuft nicht mehr per rsync auf 62.238.40.159, sondern als Container `baeroe/prevju:latest` auf Hausshost (178.105.206.129) hinter Nginx Proxy Manager, Compose in `/opt/stacks/prevju` (Dockge). Update: in Dockge „Aktualisieren“ bzw. `docker compose pull && docker compose up -d`.
- **Mixed Content hinter dem Proxy (v0.2.1):** Laravel ignorierte `X-Forwarded-Proto`, Assets kamen als `http://`. Fix: `trustProxies` für private Netze, bewusst nicht `*` (sonst `X-Forwarded-For` fälschbar, Login-Drosselung umgehbar).
- **Leerer `tests/Unit`:** PHPUnit brach in CI ab, weil Git leere Ordner nicht versioniert. Unit-Suite entfernt.
- **Tool-Namen:** `laravel/mcp` erzeugt kebab-case (`write-files`), Beschreibungen nannten zuerst `write_files`. Test prüft seitdem jeden genannten Namen.
- **Fehler im MCP:** Nur `ValidationException` kommt lesbar beim Modell an, andere Exceptions als „Something went wrong“. Pfad- und ZIP-Fehler werfen deshalb Validierungsfehler.
- **Codex:** `codex mcp add` schreibt nur User-Config und nimmt Tokens nur per Env-Var. Setup-Seite erzeugt daher einen `printf >> config.toml`-Befehl mit festem Header.
- **Docker-Hub-Beschreibung** braucht ein Token mit Read/Write/Delete, Read & Write gibt „Forbidden“.
- **GitHub-Pages-Zertifikat** kam erst, nachdem die Custom Domain einmal entfernt und neu eingetragen wurde. Namecheap-Standardeinträge (URL-Redirect auf `@`, Parking-CNAME auf `www`) mussten vorher weg.
- **Lokale Tools:** Port 5173 ist von anderen Projekten belegt (Vite hier auf 5199, Doku auf 5175). `artisan serve` reicht keine Env-Vars an den PHP-Prozess weiter, für Demo-DBs `php -S` aus `public/` nutzen. RTK kürzt `curl`/`grep`-Ausgaben, bei Bedarf `rtk proxy`.
- **Keine Lizenzdatei:** `"license": "MIT"` in `composer.json` stammte aus dem Laravel-Skelett, ein öffentliches Repo ohne `LICENSE` ist rechtlich nicht nutzbar. Behoben.
