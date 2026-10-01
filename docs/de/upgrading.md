# Aktualisieren

```bash
docker compose pull && docker compose up -d
```

Die Datenbank migriert beim Start. Die Daten im Volume bleiben erhalten. Ein reines `docker compose restart` behält das alte Image.

Version gepinnt? Dann zuerst den Tag in der `docker-compose.yml` ändern. Alle Versionen und ihre Hinweise: [GitHub-Releases](https://github.com/baeroe/prevju/releases).

## Hinweise pro Version

### 0.4

- Neu: [Projekte](/de/projects). Ein Kunden-Link und ein Passwort für mehrere Sites, neueste zuerst. Die Datenbank migriert beim Start, beim Update ist nichts zu tun. Verbundene MCP-Clients sehen die neuen Tools nach einem Reconnect.
- Passwortseiten erlauben jetzt 10 Versuche pro Minute und IP.
- Der Admin hat getrennte Seiten **Projekte** und **Sites** und startet bei den Projekten. Sites kommen per Ziehen oder über das Projekt-Label auf jeder Card in ein Projekt.
- Der Admin ist auf Englisch und Deutsch, Standard ist Englisch. Admin-URLs beginnen jetzt mit der Sprache (`/de/sites`), alte Lesezeichen wie `/sites` leiten weiter. Kundenlinks ändern sich nicht. Siehe [Einrichtung](/de/setup#sprache).
- MCP-Fehlermeldungen sind jetzt auf Englisch.
- Die Doku liegt jetzt unter `/en/` und `/de/`, alte Links leiten weiter.

### 0.3.2

- `get-compatibility` liefert jetzt dieselbe Seite wie [Was funktioniert](/de/what-works) auf prevju.dev, Agenten und Doku sagen also immer dasselbe. Beim Update ist nichts zu tun.

### 0.3.1

- Neues MCP-Tool `get-compatibility`: Agenten prüfen einen Entwurf vor dem Hochladen gegen [Was funktioniert](/de/what-works). Verbundene MCP-Clients sehen es nach einem Reconnect. Beim Update ist nichts zu tun.

### 0.3

- MCP-Server und eine neue Seite **MCP** im Admin für Tokens. Beim Update ist nichts zu tun.

### 0.2.1

- Behebt Assets, die hinter einem Reverse-Proxy als `http://` geladen wurden.

### 0.2

- Neues Admin-UI. Der Admin ist von `/admin` auf die Haupt-URL umgezogen, alte Lesezeichen auf `/admin` funktionieren nicht mehr.
- Der Standard-Host-Port in der Beispiel-Compose-Datei ist jetzt `7738`. Bestehende Setups behalten ihren Port.
