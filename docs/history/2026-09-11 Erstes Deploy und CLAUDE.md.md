---
tags: [session]
datum: 2026-09-11
---
# 2026-09-11 – Erstes Deploy und CLAUDE.md

## Kontext
Projekt stand nach zwei Commits (Upload, Share-Link, Passwort, Datei-Prune). Noch kein Hosting, CLAUDE.md enthielt nur den Laravel-Boost-Bootstrap-Text.

## Was wurde gemacht
- CLAUDE.md neu geschrieben: Zweck, Stack (PHP 8.3, Laravel 13, Filament 5, SQLite), Kommandos, Architektur der drei tragenden Dateien, Fallstricke. Enthält die Pflicht, die Datei bei Änderungen aktuell zu halten. Boost-Bootstrap entfernt, Boost ist nicht installiert. `AGENTS.md` hat den Boost-Text noch.
- Erstes Deploy auf https://prevju.hauss.dev (Pfad `/home/prevju/prevju.hauss.dev`, SSH-User `prevju`) per rsync.
- Auf dem Server: `composer install --no-dev`, `.env` angelegt, `key:generate`, `migrate`, `app:ensure-admin`, `filament:assets`, `optimize`.
- Admin-User aus `ADMIN_EMAIL`/`ADMIN_PASSWORD` in der Server-`.env`.

## Entscheidungen & Warum
- **Nativ statt Docker.** README beschreibt nur den Docker-Weg. Auf dem Server existierte aber schon ein nginx-vhost für die Domain mit PHP-FPM 8.4, und der User `prevju` hat weder Docker-Socket-Zugriff noch sudo. SQLite + PHP-FPM reichen völlig.
- **SQLite bleibt** auch in Produktion. Liegt unter `storage/app/database.sqlite`, neben den Uploads in `storage/app/sites/`.
- **rsync-Excludes:** `.git`, `vendor`, `.env`, `storage/app/*`, `database.sqlite`. Uploads und DB dürfen bei einem Redeploy nie überschrieben werden.
- Onboarding-HTML (explain-project-Skill) bewusst weggelassen, Repo ist klein genug für eine Chat-Erklärung.

## Stolpersteine
- CLAUDE.md-Entwurf behauptete zuerst "Upload-Limit 100 MB an drei Stellen". Dockerfile setzt aber 200M. Korrigiert: App-Limit 100 MB in `SiteForm` und `AppServiceProvider`, Dockerfile/nginx-Limit muss nur darüber liegen.
- Lokale DB hatte User `admin@example.com` mit unbekanntem Passwort. Reset über tinker oder `make:filament-user`.

## Redeploy
```bash
rsync -az --delete --exclude .git --exclude vendor --exclude node_modules --exclude .env \
  --exclude 'storage/app/*' --exclude 'storage/logs/*' --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/views/*' \
  --exclude database/database.sqlite --exclude .phpunit.result.cache \
  ./ prevju@62.238.40.159:/home/prevju/prevju.hauss.dev/
ssh prevju@62.238.40.159 'cd prevju.hauss.dev && composer install --no-dev -n && php artisan migrate --force && php artisan optimize'
```
