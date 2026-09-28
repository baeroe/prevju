# Was funktioniert

Jede Site wird unter `<APP_URL>/s/<slug>/` ausgeliefert, nicht im Root der Domain. Das entscheidet, was geht:

| Entwurf | Geht? | Hinweis |
|---|---|---|
| Statisches HTML/CSS/JS | ✅ | |
| Links zwischen Seiten (`about.html`, `blog/`) | ✅ | Nur relative Links. `blog/` liefert `blog/index.html` |
| Absolute Pfade ab Root (`/about.html`, `/img/logo.png`) | ❌ | Zeigen auf das prevju-Root. Relative Pfade nutzen (`img/logo.png`) |
| Vite-/CRA-Build mit Standardeinstellungen | ❌ | Erzeugt `/assets/…`. Mit relativem `base` bauen, siehe unten |
| SPA mit Hash-Routing (`HashRouter`, `createWebHashHistory`) | ✅ | Mit relativem `base` |
| SPA mit History-Routing (`BrowserRouter`) | ⚠️ | Geht, aber der Build muss den Slug der Site kennen, siehe unten |
| Neu laden / Deep-Link in eine SPA-Route | ✅ | Unbekannte Pfade ohne Dateiendung liefern die `index.html` der Site |
| Server-Code (PHP, Node, APIs) | ❌ | Nur Dateien |

**Vite, Hash-Routing** (am einfachsten, funktioniert mit jedem Slug):

```js
// vite.config.js
export default { base: './' }
```

**Vite, History-Routing** (saubere URLs, pro Site neu bauen):

```js
// vite.config.js — Slug aus dem Link der Site in prevju
export default { base: '/s/abc123xyz0/' }
```

```jsx
<BrowserRouter basename={import.meta.env.BASE_URL}>
```

Bei Create React App stattdessen `"homepage": "."` in der `package.json` setzen.
