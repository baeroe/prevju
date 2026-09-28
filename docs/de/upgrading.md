# Aktualisieren

```bash
docker compose pull && docker compose up -d
```

Die Datenbank migriert beim Start. Die Daten im Volume bleiben erhalten. Ein reines `docker compose restart` behält das alte Image.

Version gepinnt? Dann zuerst den Tag in der `docker-compose.yml` ändern. Alle Versionen und ihre Hinweise: [GitHub-Releases](https://github.com/baeroe/prevju/releases).

## Hinweise pro Version

### 0.3

- MCP-Server und eine neue Seite **MCP** im Admin für Tokens. Beim Update ist nichts zu tun.

### 0.2.1

- Behebt Assets, die hinter einem Reverse-Proxy als `http://` geladen wurden.

### 0.2

- Neues Admin-UI. Der Admin ist von `/admin` auf die Haupt-URL umgezogen, alte Lesezeichen auf `/admin` funktionieren nicht mehr.
- Der Standard-Host-Port in der Beispiel-Compose-Datei ist jetzt `7738`. Bestehende Setups behalten ihren Port.
