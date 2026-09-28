# What works

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
