# Setup

prevju runs as a single Docker container. You need two files and a reverse proxy for HTTPS.

## docker-compose.yml

```yaml
services:
  prevju:
    image: baeroe/prevju:latest
    restart: unless-stopped
    ports:
      - "7738:8080"
    environment:
      APP_URL: "${APP_URL}"
      ADMIN_EMAIL: "${ADMIN_EMAIL}"
      ADMIN_PASSWORD: "${ADMIN_PASSWORD}"
    volumes:
      - prevju-data:/var/www/html/storage/app

volumes:
  prevju-data:
```

## .env

Next to the compose file:

```bash
APP_URL=https://preview.example.com
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me
```

`APP_URL` is the public address including `https://`. Client links are built from it.

## Start

```bash
docker compose up -d
```

Open `APP_URL` and log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`. The admin user is created on the first start.

## What lives where

- **Data:** SQLite database, uploaded sites and the generated `APP_KEY` are in the `prevju-data` volume. Containers can be recreated freely, the volume stays. Don't run `docker compose down -v`, that deletes it.
- **Client links:** `APP_URL/s/<slug>/`, one random slug per site.
- **Port:** `7738` on the host, `8080` inside the container. Put a reverse proxy in front for HTTPS, see [Reverse proxy](/en/reverse-proxy).
- **Versions:** `latest` follows every release. Pin one with `image: baeroe/prevju:0.3.1`.
