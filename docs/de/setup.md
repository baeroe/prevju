# Einrichtung

prevju läuft als einzelner Docker-Container. Du brauchst zwei Dateien und einen Reverse-Proxy für HTTPS.

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

Neben die Compose-Datei:

```bash
APP_URL=https://preview.example.com
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me
```

`APP_URL` ist die öffentliche Adresse inklusive `https://`. Daraus entstehen die Kundenlinks.

## Starten

```bash
docker compose up -d
```

Öffne `APP_URL` und melde dich mit `ADMIN_EMAIL` / `ADMIN_PASSWORD` an. Der Admin-User wird beim ersten Start angelegt.

## Sprache

Admin, Passwortseiten und Kundenseiten gibt es auf Englisch und Deutsch. Standard ist Englisch.

- **Admin:** Die Sprache steht in der URL, `APP_URL/en/sites` oder `APP_URL/de/sites`. Umschalten mit **EN / DE** im Header.
- **Kundenseiten:** Die Links, die du verschickst, bleiben gleich. Kunden schalten mit **EN / DE** oben um, die Wahl merkt sich ihr Browser. Deine eigene Wahl im Admin gilt auch für Kundenseiten, die du im selben Browser öffnest.

## Was wo liegt

- **Daten:** SQLite-Datenbank, hochgeladene Sites und der erzeugte `APP_KEY` liegen im Volume `prevju-data`. Container kannst du beliebig neu erstellen, das Volume bleibt. Kein `docker compose down -v`, das löscht es.
- **Kundenlinks:** `APP_URL/s/<slug>/`, ein zufälliger Slug pro Site.
- **Port:** `7738` auf dem Host, `8080` im Container. Für HTTPS kommt ein Reverse-Proxy davor, siehe [Reverse-Proxy](/de/reverse-proxy).
- **Versionen:** `latest` folgt jedem Release. Eine feste Version pinnst du mit `image: baeroe/prevju:0.3.1`.
