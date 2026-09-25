# prevju

Upload HTML drafts, send the link to your client. Optionally password protected.

## Self-hosting (Docker Compose)

No code needed, just two files. `docker-compose.yml`:

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

`.env` next to it:

```bash
APP_URL=https://preview.example.com
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me
```

```bash
docker compose up -d                              # start
docker compose pull && docker compose up -d       # update
```

- Admin: `<APP_URL>/admin` (log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`, the user is created on startup)
- Client link: `<APP_URL>/s/<slug>/`
- Data (SQLite, uploads, generated `APP_KEY`) lives in the `prevju-data` volume.
- HTTPS via a reverse proxy (Caddy/Traefik) in front of port 8080.
- Pin a version instead of `latest`: `image: baeroe/prevju:1.0.0`

### Release

Push a git tag, GitHub Actions builds the image for amd64 and arm64 and pushes it to Docker Hub:

```bash
git tag v1.0.0 && git push origin v1.0.0
```

## Upload

Single files (HTML, CSS, JS, images) or a ZIP with a folder structure. A single wrapper folder inside the ZIP is removed. Without an `index.html`, the first `.html` file is served.

## Local development

```bash
composer install && php artisan migrate && php artisan make:filament-user
php artisan serve   # http://localhost:8000/admin
php artisan test
```
