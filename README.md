# prevju

HTML-Entwürfe hochladen, Link an den Kunden schicken. Optional mit Passwort.

## Betrieb (Docker Compose)

```bash
cp .env.docker.example .env      # APP_KEY, APP_URL, ADMIN_EMAIL, ADMIN_PASSWORD setzen
docker compose run --rm prevju php artisan key:generate --show   # → APP_KEY
docker compose up -d --build
```

- Admin: `https://<APP_URL>/admin` (Login mit `ADMIN_EMAIL` / `ADMIN_PASSWORD`, User wird beim Start angelegt)
- Kundenlink: `https://<APP_URL>/s/<slug>/`
- Daten (SQLite + Uploads) liegen im Volume `prevju-data`.
- HTTPS über vorgeschalteten Reverse-Proxy (Caddy/Traefik) auf Port 8080.

### Image bauen und in Registry pushen

```bash
docker build -t registry.example.com/prevju:latest .
docker push registry.example.com/prevju:latest
# auf dem Server: PREVJU_IMAGE=registry.example.com/prevju:latest in .env, dann docker compose up -d
```

## Upload

Einzelne Dateien (HTML, CSS, JS, Bilder) oder eine ZIP mit Ordnerstruktur. Ein einzelner Wrapper-Ordner in der ZIP wird entfernt. Ohne `index.html` wird die erste `.html` ausgeliefert.

## Lokal entwickeln

```bash
composer install && php artisan migrate && php artisan make:filament-user
php artisan serve   # http://localhost:8000/admin
php artisan test
```
