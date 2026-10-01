# Upgrading

```bash
docker compose pull && docker compose up -d
```

The database migrates on start. Data in the volume stays. `docker compose restart` alone keeps the old image.

Pinned a version? Change the tag in `docker-compose.yml` first. All versions and their notes: [GitHub releases](https://github.com/baeroe/prevju/releases).

## Notes per version

### 0.4

- New: [projects](/en/projects). One client link and one password for several sites, newest first. The database migrates on start, nothing to change on upgrade. Connected MCP clients see the new tools after a reconnect.
- Password pages now allow 10 attempts per minute and IP.

### 0.3.2

- `get-compatibility` now returns the same page as [What works](/en/what-works) on prevju.dev, so agents and the docs always agree. Nothing to change on upgrade.

### 0.3.1

- New MCP tool `get-compatibility`: agents check a draft against [What works](/en/what-works) before uploading. Connected MCP clients see it after a reconnect. Nothing to change on upgrade.

### 0.3

- MCP server and a new **MCP** page in the admin for tokens. Nothing to change on upgrade.

### 0.2.1

- Fixes assets loading as `http://` behind a reverse proxy.

### 0.2

- New admin UI. The admin moved from `/admin` to the root URL, old bookmarks to `/admin` stop working.
- The default host port in the example compose file is now `7738`. Existing setups keep their port.
