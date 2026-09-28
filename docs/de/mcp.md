# MCP

prevju hat einen MCP-Server unter `<APP_URL>/mcp`. Damit legen Claude Code oder Codex Sites an, laden Entwürfe hoch und geben dir den Kundenlink.

## Verbinden

Leg im Admin unter **MCP** ein Token an. Die Seite erzeugt den Befehl mit deinem Token, für Claude Code oder Codex, für alle Projekte oder nur das aktuelle.

**Claude Code** (`--scope user` für alle Projekte, `--scope local` für das aktuelle):

```bash
claude mcp add --transport http --scope user prevju https://preview.example.com/mcp --header "Authorization: Bearer <token>"
```

**Codex:** das hier in `~/.codex/config.toml` (alle Projekte) oder in `.codex/config.toml` eines vertrauenswürdigen Projekts eintragen. Die Projektdatei gehört nicht ins Git, sie enthält das Token.

```toml
[mcp_servers.prevju]
url = "https://preview.example.com/mcp"
http_headers = { "Authorization" = "Bearer <token>" }
```

Ein Token hat dieselben Rechte wie der Admin-Login. Leg pro Gerät eins an, dann kannst du sie einzeln widerrufen.

## Tools

| Tool | Was es macht |
|---|---|
| `get-compatibility` | Liefert [Was funktioniert](/de/what-works). Agenten rufen es vor dem Hochladen auf |
| `list-sites`, `get-site` | Sites mit Link, Passwortstatus und Dateien |
| `create-site` | Neue Site, optional mit Passwort |
| `update-site` | Umbenennen, Passwort setzen oder entfernen |
| `write-files` | Generierte Textdateien (HTML, CSS, JS) direkt schreiben |
| `get-upload-url` | Signierte URL für 15 Minuten, um eine ZIP oder Binärdatei mit `curl` hochzuladen |
| `delete-file`, `clear-files`, `delete-site` | Destruktiv, ohne Rückgängig |

Für eine neue Version eines Entwurfs laden Agenten mit `replace` hoch: Die Live-Site wechselt in einem Schritt und ist zwischendurch nie leer. Ein fehlgeschlagener Upload ändert nichts.

## Grenzen

- ZIPs und andere Binärdateien gehen per `curl` an eine signierte URL, dafür braucht der Client eine Shell (Claude Code, Codex).
- Connectors in claude.ai und Claude Desktop brauchen OAuth, das prevju noch nicht unterstützt.
