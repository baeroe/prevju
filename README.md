# prevju

[![Tests](https://github.com/baeroe/prevju/actions/workflows/tests.yml/badge.svg)](https://github.com/baeroe/prevju/actions/workflows/tests.yml)
[![Docker Hub](https://img.shields.io/docker/v/baeroe/prevju?sort=semver&label=docker%20hub)](https://hub.docker.com/r/baeroe/prevju)

Upload HTML drafts, send the link to your client. Optionally password protected.

## Self-hosting (Docker Compose)

No code needed, just two files. `docker-compose.yml`:

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

- Admin: `<APP_URL>` (log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`, the user is created on startup)
- Client link: `<APP_URL>/s/<slug>/`
- Data (SQLite, uploads, generated `APP_KEY`) lives in the `prevju-data` volume.
- HTTPS via a reverse proxy (Caddy/Traefik) in front of port 7738.
- Pin a version instead of `latest`: `image: baeroe/prevju:1.0.0`

### Release

Push a git tag, GitHub Actions builds the image for amd64 and arm64 and pushes it to Docker Hub:

```bash
git tag v1.0.0 && git push origin v1.0.0
```

## Upload

Drag a folder, single files (HTML, CSS, JS, images) or a ZIP onto the site. Folder structure is kept; a single wrapper folder (`dist/…` or inside the ZIP) is removed. Without an `index.html`, the first `.html` file is served. See [What works](#what-works) for links and SPAs.

## What works

Every site is served under `<APP_URL>/s/<slug>/`, not at the domain root. That decides what works:

| Draft | Works? | Notes |
|---|---|---|
| Static HTML/CSS/JS | ✅ | |
| Links between pages (`about.html`, `blog/`) | ✅ | Relative links only. `blog/` serves `blog/index.html` |
| Root-absolute paths (`/about.html`, `/img/logo.png`) | ❌ | Point to the prevju root. Use relative paths (`img/logo.png`) |
| Vite / CRA build with default settings | ❌ | Emits `/assets/…`. Build with a relative base, see below |
| SPA with hash routing (`HashRouter`, `createWebHashHistory`) | ✅ | With a relative base |
| SPA with history routing (`BrowserRouter`) | ⚠️ | Works, but the build must know the site's slug, see below |
| Reload / deep link into an SPA route | ✅ | Unknown paths without a file extension serve the site's `index.html` |
| Server code (PHP, Node, APIs) | ❌ | Files only |

**Vite, hash routing** (simplest, works for any slug):

```js
// vite.config.js
export default { base: './' }
```

**Vite, history routing** (clean URLs, rebuild per site):

```js
// vite.config.js — slug from the site's link in prevju
export default { base: '/s/abc123xyz0/' }
```

```jsx
<BrowserRouter basename={import.meta.env.BASE_URL}>
```

For Create React App set `"homepage": "."` in `package.json` instead of `base`.
