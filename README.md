# prevju

HTML-Entwürfe hochladen, Link an den Kunden schicken. Optional mit Passwort.

## Self-Hosting (Docker Compose)

Kein Code nötig, nur zwei Dateien. `docker-compose.yml`:

```yaml
services:
  prevju:
    image: baeroe/prevju:latest
    restart: unless-stopped
    ports:
      - "8080:8080"
    environment:
      APP_URL: "${APP_URL}"
      ADMIN_EMAIL: "${ADMIN_EMAIL}"
      ADMIN_PASSWORD: "${ADMIN_PASSWORD}"
    volumes:
      - prevju-data:/var/www/html/storage/app

volumes:
  prevju-data:
```

`.env` daneben:

```bash
APP_URL=https://preview.example.com
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me
```

```bash
docker compose up -d                              # starten
docker compose pull && docker compose up -d       # updaten
```

- Admin: `<APP_URL>/admin` (Login mit `ADMIN_EMAIL` / `ADMIN_PASSWORD`, User wird beim Start angelegt)
- Kundenlink: `<APP_URL>/s/<slug>/`
- Daten (SQLite, Uploads, generierter `APP_KEY`) liegen im Volume `prevju-data`.
- HTTPS über vorgeschalteten Reverse-Proxy (Caddy/Traefik) auf Port 8080.
- Feste Version statt `latest`: `image: baeroe/prevju:1.0.0`

### Release

Git-Tag pushen, GitHub Actions baut das Image für amd64 und arm64 und pusht es nach Docker Hub:

```bash
git tag v1.0.0 && git push origin v1.0.0
```

## Upload

Einzelne Dateien (HTML, CSS, JS, Bilder) oder eine ZIP mit Ordnerstruktur. Ein einzelner Wrapper-Ordner in der ZIP wird entfernt. Ohne `index.html` wird die erste `.html` ausgeliefert.

## Lokal entwickeln

```bash
composer install && php artisan migrate && php artisan make:filament-user
php artisan serve   # http://localhost:8000/admin
php artisan test
```
