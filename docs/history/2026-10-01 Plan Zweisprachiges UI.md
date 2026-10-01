# Zweisprachiges UI und lokalisierte Screenshots – Implementierungsplan

> **Für ausführende Agenten:** REQUIRED SUB-SKILL: superpowers:executing-plans. Schritte sind Checkboxen (`- [ ]`).

**Ziel:** Admin, Login, Passwortseite und Kundenseite gibt es auf Englisch und Deutsch, umschaltbar im Header. Danach bekommt die Doku neue Screenshots, getrennt nach Sprache.

**Architektur:** Echte Schlüssel (`sites.new`) in `lang/en.json` und `lang/de.json`. PHP übersetzt mit `__()`. React bekommt die Übersetzungen der aktiven Sprache als Inertia-Shared-Prop und übersetzt mit `t()` aus `resources/js/lib/i18n.ts`.
- **Admin:** Alle Admin-Routen liegen unter `/{locale}` (`/en/sites`, `/de/projects/3`, `/en/login`, `/en/tokens`). Das Präfix bestimmt die Sprache und wird zusätzlich im Cookie `locale` gemerkt. `URL::defaults(['locale' => …])` sorgt dafür, dass `route()` das Präfix selbst setzt. Das Frontend baut Admin-URLs mit `path()` aus `lib/i18n.ts`. Alte URLs ohne Präfix (`/`, `/sites…`, `/projects…`, `/login`, `/tokens`) leiten per GET auf die gemerkte Sprache weiter, sonst auf `/en`.
- **Kundenseiten** (`/s/…`, `/project/…`, Passwortseite) behalten ihre URLs. Die Sprache kommt aus dem Cookie `locale`, sonst Englisch. Ihr Umschalter ist `GET /locale/{de|en}`: Er setzt das Cookie und leitet zurück.
- **Default:** immer Englisch, keine Auswertung von `Accept-Language`.
- **Doku:** Die englischen Seiten wandern nach `docs/en/`, die deutschen bleiben in `docs/de/`. Pfade ohne Sprachpräfix leiten im Browser auf `/en/…` weiter (Theme-Hook, greift auch auf der 404-Seite von GitHub Pages).

**Tech Stack:** Laravel 13 Translator (JSON-Dateien), Inertia v3 Shared Props, React 19, Playwright für die Screenshots.

**Spec:** Abstimmung mit dem Nutzer am 2026-10-01:
- Admin zweisprachig, mit Sprachumschalter im Header. Default Englisch.
- Sprache als `/en` bzw. `/de` in der URL: im Admin und in der Doku. Kundenlinks bleiben unverändert.
- Der Umschalter erscheint auch auf der Kundenseite, der Passwortseite und dem Login.
- Übersetzungen über echte Schlüssel, nicht über den deutschen Text.
- Neue Screenshots: Übersicht mit Projekten, Kundenseite eines Projekts, Admin-Projektseite, Passwortseite, MCP-Seite. Je eine Fassung pro Sprache: EN-Doku zeigt EN-Bilder, DE-Doku zeigt DE-Bilder.

## Global Constraints

- Git-Identität: nur `Rafael Haußmann <rafael.hingerl@gmail.com>`, vor jedem Commit `git config user.email` prüfen. Commits enden mit `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Branch: `feature/projects` (gleicher PR wie Projekte).
- Sprachen: `en` und `de`. Fallback: `en`.
- Schlüssel: kleingeschrieben, Punkt-Namensräume nach Bereich (`common.`, `nav.`, `login.`, `sites.`, `site.`, `projects.`, `project.`, `upload.`, `files.`, `password.`, `client.`, `tokens.`, `time.`). Platzhalter im Laravel-Stil `:name`.
- `lang/en.json` und `lang/de.json` haben exakt dieselben Schlüssel (Test).
- Jeder im Code genutzte Schlüssel (`t('…')`, `__('…')`) existiert in beiden Dateien (Test).
- MCP-Fehlermeldungen: fest auf Englisch, nicht übersetzt (das MCP-Endpoint läuft ohne Locale-Middleware).
- Keine neue Abhängigkeit.
- Doku auf Englisch und Deutsch anpassen.

## Review Focus

1. **Alte Lesezeichen/Links ohne Präfix** (`/sites`, `/sites/3`, `/login`, prevju.dev/setup): leiten weiter statt 404. → Test `test_unprefixed_admin_urls_redirect`, Doku im Browser prüfen.
2. **Ungültiges Locale** in URL (`/fr/sites`) oder Cookie (`../../etc`): 404 bzw. Englisch, nie ein Fehler oder Pfad in den Translator. → Tests `test_unknown_locale_prefix_is_404`, `test_unknown_cookie_locale_is_ignored`.
3. **Redirects aus Controllern** (Login, Anlegen, `back()`, Auth-Redirect auf Login) behalten das Präfix der aktuellen Sprache. → Admin-Tests prüfen `/de/…`-Ziele.
4. **Zurückleiten nach `/locale/{x}`** auf eine fremde Domain über einen manipulierten Referer: nur innerhalb der App. → Test `test_locale_switch_redirects_back_only_inside_the_app`.
5. **Fehlende Übersetzung** fällt als roher Schlüssel auf. → Schlüssel-Test über alle `t()`/`tn()`/`__()`-Aufrufe.

---

### Task 1: Locale-Infrastruktur und Präfix-Routen

**Files:** Create `app/Http/Middleware/SetLocale.php`, `app/Http/Controllers/LocaleController.php`, `lang/en.json`, `lang/de.json`, `tests/Feature/LocaleTest.php`. Modify `bootstrap/app.php`, `routes/web.php`, `HandleInertiaRequests.php`, `app.blade.php`, alle Admin-Controller-Redirects, `tests/Feature/{AdminTest,TokenTest,ProjectTest,SiteTest}.php` (Admin-Pfade auf `/en/…`).

**Produces:** Shared Props `locale`, `translations`. Benannte Admin-Routen (`login`, `sites.index`, `sites.show`, `projects.show`, `tokens.index`, …). `GET /locale/{locale}` (Name `locale`). Cookie `locale`.

- [ ] **Step 1: Failing Tests** `tests/Feature/LocaleTest.php`

```php
public function test_default_is_english(): void
{
    $this->withHeader('Accept-Language', 'de-DE,de')->get('/en/login')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en')->where('translations.login\.submit', 'Log in'));
}

public function test_prefix_picks_language_and_is_remembered(): void
{
    $this->get('/de/login')->assertSee('lang="de"', false)->assertCookie('locale', 'de')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'de'));
}

public function test_unprefixed_admin_urls_redirect(): void
{
    $this->get('/')->assertRedirect('/en/sites');
    $this->get('/login')->assertRedirect('/en/login');
    $this->withCookie('locale', 'de')->get('/sites/3')->assertRedirect('/de/sites/3');
}

public function test_unknown_locale_prefix_is_404(): void
{
    $this->get('/fr/sites')->assertNotFound();
}

public function test_unknown_cookie_locale_is_ignored(): void
{
    $this->withCookie('locale', '../../etc')->get('/sites')->assertRedirect('/en/sites');
}

public function test_client_pages_follow_cookie_and_switch(): void
{
    // a password page is the simplest client page: Site with password, no files needed
    Site::create(['name' => 'Test', 'slug' => 'loc123', 'password' => Hash::make('x')]);
    $this->get('/s/loc123/')->assertSee('Open draft');
    $this->from('/s/loc123/')->get('/locale/de')->assertRedirect('/s/loc123/')->assertCookie('locale', 'de');
    $this->withCookie('locale', 'de')->get('/s/loc123/')->assertSee('Entwurf öffnen');
}

public function test_locale_switch_rejects_unknown_locale(): void
{
    $this->get('/locale/fr')->assertNotFound();
}

public function test_locale_switch_redirects_back_only_inside_the_app(): void
{
    $this->withHeader('Referer', 'https://evil.example/phish')->get('/locale/en')->assertRedirect(url('/'));
}

public function test_both_languages_have_the_same_keys(): void { /* wie zuvor geplant: array_diff beider Schlüssellisten leer */ }

public function test_every_key_used_in_code_exists(): void { /* Regex über t(/tn(/__( in resources/js, app, resources/views; tn-Schlüssel mit .one/.other prüfen */ }
```

(Im Test-Assert `translations.login\.submit`: Inertias `where` trennt an Punkten. Beim Umsetzen klären, ob die Shared-Prop flach bleibt und der Test `->where('translations', fn ($t) => $t['login.submit'] === 'Log in')` nutzt. Ruling notieren.)

- [ ] **Step 2:** Tests laufen lassen → FAIL.

- [ ] **Step 3: Middleware** `SetLocale` (Web-Gruppe, vor `HandleInertiaRequests`):

```php
public const LOCALES = ['en', 'de'];

public function handle(Request $request, Closure $next)
{
    $prefix = $request->route('locale');
    $cookie = $request->cookie('locale');
    $locale = in_array($prefix, self::LOCALES, true) ? $prefix : (in_array($cookie, self::LOCALES, true) ? $cookie : 'en');

    app()->setLocale($locale);
    URL::defaults(['locale' => $locale]);
    // controllers don't take the prefix as an argument
    $request->route()?->forgetParameter('locale');

    $response = $next($request);
    if ($prefix && $cookie !== $locale) {
        $response->headers->setCookie(cookie()->forever('locale', $locale));
    }

    return $response;
}
```

Achtung: Route-Parameter werden erst nach dem Routing gebunden. Läuft `SetLocale` als Web-Gruppen-Middleware, ist `$request->route()` bereits gesetzt (Gruppen-Middleware läuft nach dem Matching). Beim Umsetzen prüfen.

- [ ] **Step 4: Routen** `routes/web.php`

```php
Route::prefix('{locale}')->where(['locale' => 'en|de'])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    });
    Route::middleware('auth')->group(function () { /* alle bisherigen Admin-Routen, mit Namen für die GET-Seiten */ });
});

// bookmarks and links from before the language prefix
Route::get('/{path?}', fn (Request $request, string $path = 'sites') => redirect('/'.app()->getLocale().'/'.$path))
    ->where('path', '(sites|projects|tokens|login)(/.*)?');
Route::get('/locale/{locale}', LocaleController::class)->name('locale');
```

Die Redirect-Route steht nach den Kundenrouten, damit `/s/…`, `/p/…`, `/project/…`, `/upload/…`, `/mcp` und `/up` nie greifen. `guest`-Middleware: `redirectUsersTo` in `bootstrap/app.php` auf `fn () => route('sites.index')`. Auth-Redirect auf `route('login')` funktioniert dank `URL::defaults`.

- [ ] **Step 5: Controller-Redirects** – `redirect("/sites/{$site->id}")` → `redirect()->route('sites.show', $site)`, ebenso Projekte, Tokens, Login (`intended(route('sites.index'))`), Logout (`route('login')`).

- [ ] **Step 6:** `LocaleController` (Kundenseiten-Umschalter, wie zuvor geplant: Locale prüfen, Cookie setzen, nur innerhalb der App zurückleiten). Shared Props wie zuvor geplant. `app.blade.php`: `lang="{{ app()->getLocale() }}"`.

- [ ] **Step 7: Bestehende Tests** – Admin-Pfade auf `/en/…` umstellen (`/sites` → `/en/sites`, `/projects` → `/en/projects`, `/tokens` → `/en/tokens`, `/login` → `/en/login`, `assertRedirect('/login')` → `assertRedirect('/en/login')`). Kundenpfade und `/mcp` bleiben.

- [ ] **Step 8:** `php artisan test` grün (bis auf `test_every_key_used_in_code_exists`, das erst in Task 2 grün wird), Commit „Add language prefix for admin routes and locale switch for client pages“.

### Task 2: Texte extrahieren

**Files:** Create `resources/js/lib/i18n.ts`, `resources/js/components/locale-switch.tsx`. Modify alle Seiten und Komponenten mit Texten (`pages/login.tsx`, `pages/tokens.tsx`, `pages/sites/index.tsx`, `pages/sites/show.tsx`, `pages/projects/show.tsx`, `pages/projects/public.tsx`, `components/app-layout.tsx`, `copy-link.tsx`, `dropzone.tsx`, `link-bar.tsx`, `new-site-dialog.tsx`, `settings-forms.tsx`, `site-preview.tsx`, `site-tile.tsx`, `project-preview.tsx`, `lib/format.ts`, `lib/upload.ts`, `lib/delete-with-undo.ts`), `resources/js/app.tsx`, `resources/views/site-password.blade.php`, `app/Http/Controllers/SiteController.php`, `ProjectController.php`, `LoginController.php`, `app/Models/Site.php`, MCP-Tools (Englisch fest), `lang/*.json`, `tests/Feature/SiteTest.php`, `ProjectTest.php`.

- [ ] **Step 1: `lib/i18n.ts`**

```ts
type Translations = Record<string, string>;

let locale = 'en';
let translations: Translations = {};

/** Set once per page load from the shared Inertia props; switching language reloads the page. */
export function initI18n(props: { locale?: string; translations?: Translations }) {
    locale = props.locale ?? 'en';
    translations = props.translations ?? {};
}

export function getLocale(): string {
    return locale;
}

/** Translation for a key, with :placeholders replaced. A missing key shows up as the key itself. */
export function t(key: string, params: Record<string, string | number> = {}): string {
    let text = translations[key] ?? key;
    for (const [name, value] of Object.entries(params)) text = text.replaceAll(`:${name}`, String(value));
    return text;
}

/** Singular/plural pair: key.one / key.other, :count replaced. */
export function tn(key: string, count: number): string {
    return t(count === 1 ? `${key}.one` : `${key}.other`, { count });
}
```

`app.tsx`: in `setup` vor `createRoot`: `initI18n(props.initialPage.props as never);`

`test_every_key_used_in_code_exists` findet `tn('files.count', …)` als Basis-Schlüssel. Ruling beim Umsetzen: den Regex so anpassen, dass bei `tn(` die Schlüssel `.one`/`.other` geprüft werden.

In `lib/i18n.ts` zusätzlich:

```ts
/** Admin URL in the current language: path('/sites/3') -> '/de/sites/3'. */
export function path(p: string): string {
    return `/${locale}${p}`;
}
```

Alle Admin-URLs im Frontend (`href`, `router.*`, `form.*`, `deleteWithUndo`, `uploadFile`) laufen über `path()`.

- [ ] **Step 2: `components/locale-switch.tsx`**

```tsx
import { getLocale, t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const LOCALES = [
    ['de', 'DE', 'Deutsch'],
    ['en', 'EN', 'English'],
] as const;

/** Admin pages swap the /en|/de prefix; client pages go through /locale/{code}, which sets the cookie. Full reload either way. */
function switchUrl(code: string): string {
    const { pathname, search } = window.location;
    return /^\/(en|de)(\/|$)/.test(pathname) ? pathname.replace(/^\/(en|de)/, `/${code}`) + search : `/locale/${code}`;
}

export function LocaleSwitch({ className }: { className?: string }) {
    return (
        <nav aria-label={t('nav.language')} className={cn('flex text-sm', className)}>
            {LOCALES.map(([code, short, name]) => (
                <a
                    key={code}
                    href={switchUrl(code)}
                    lang={code}
                    aria-label={name}
                    aria-current={getLocale() === code ? 'true' : undefined}
                    className="px-1.5 py-1 text-ink-muted hover:text-ink aria-[current]:font-medium aria-[current]:text-ink"
                >
                    {short}
                </a>
            ))}
        </nav>
    );
}
```

Einbau: `app-layout.tsx` im Header vor dem MCP-Button; `login.tsx` und `projects/public.tsx` oben rechts (`absolute top-4 right-6`); Blade `site-password` dasselbe Markup mit `app()->getLocale()`.

- [ ] **Step 3: Extraktion** – jede Datei aus der Liste: jeden sichtbaren Text, jedes `aria-label`, jeden Tooltip, jeden Toast und jeden `<Head title>` durch `t('…')` ersetzen, die Übersetzungen in beide JSON-Dateien schreiben (Deutsch = bisheriger Text, Englisch = Übersetzung). Plurale über `tn()` (`files.count`, `sites.count`). `lib/format.ts`: `Intl.RelativeTimeFormat(getLocale(), …)`, „gerade eben“ → `t('time.just_now')`.
  Backend: `__('password.wrong')` in Site-/ProjectController, `__('login.failed')` im LoginController, `__('files.invalid_path', ['path' => $path])` und `__('files.zip_unreadable')` in `Site.php`. Passwortseite: `__('password.label')`, Controller übergeben `intro`/`button` als übersetzte Texte (`client.intro_site`, `client.open_site`, `client.intro_project`, `client.open_project`).
  MCP: `SiteTool` „No site with ID :id.“, `DeleteFile` „File :path does not exist in this site.“, `WriteFiles` „More than 2 MB in total. Upload larger drafts as a zip via get-upload-url.“ – fest auf Englisch.

- [ ] **Step 4: Tests anpassen** – `SiteTest::test_password_protected_site`: `->assertSee('Password')` (Default Englisch). `ProjectTest`: `->assertSee('Open drafts')`, dazu eine Abfrage mit `withCookie('locale', 'de')` → `assertSee('Entwürfe öffnen')`.

- [ ] **Step 5:** `npx tsc -p . && php artisan test && vendor/bin/pint --test` → grün. Restliche deutsche Texte suchen: `grep -rnE "[äöüÄÖÜß]" resources/js app resources/views` darf nur noch Kommentare/Testdaten zeigen.

- [ ] **Step 6: Browser** – Admin, Login, Passwortseite, Kundenseite in beiden Sprachen; Umschalter wechselt und bleibt auf derselben Seite; 375 px ohne horizontales Scrollen.

- [ ] **Step 7:** Commit „Translate admin and client pages (en/de)“.

### Task 3: Screenshots

**Files:** `docs/public/screenshots/{en,de}/{sites,project,client,password,mcp,site}.png` (neu), alte `docs/public/screenshots/sites.png`/`site.png` löschen. Modify `docs/index.md`, `docs/de/index.md`, `docs/uploading.md`, `docs/de/uploading.md`, `docs/projects.md`, `docs/de/projects.md`, `docs/mcp.md`, `docs/de/mcp.md`, `docs/.vitepress/config.ts` (og:image), `README.md`.

- [ ] **Step 1: Demo-Daten** – Skript im Scratchpad (nicht im Repo), eigene SQLite-Datei, Sites mit eigenem HTML je Sprache. EN: „Bakery Kurz“ (Projekt, Passwort, Startseite v1–v4), „Autumn campaign“, „Landing page Northern Lights“, „Trade fair stand Hannover“, „Portfolio Studio Wendt“. DE: dieselben Motive mit deutschen Namen und Texten (wie die bisherigen Screenshots). Zwei MCP-Tokens („MacBook“, „Studio iMac“). Zeitstempel gestaffelt (Minuten, Stunden, Tage).
- [ ] **Step 2: Aufnahmen** – Playwright, Viewport 1440×900, Sprache per `/locale/{en,de}`, Slider vor dem Screenshot auf die erste Folie (reduzierte Bewegung emulieren). Motive: `sites` (Übersicht), `site` (Site-Seite mit Dateien), `project` (Admin-Projektseite), `client` (Kundenseite, als Gast nach Freischalten), `password` (Passwortseite des Projekts, als Gast), `mcp` (Token-Seite). Jede Aufnahme ansehen, bevor sie übernommen wird.
- [ ] **Step 3: Einbinden** – EN-Seiten `/screenshots/en/…`, DE-Seiten `/screenshots/de/…`, jeweils mit Alt-Text in der Sprache der Seite:
  - `index.md`: `sites`
  - `uploading.md`: `site`
  - `projects.md`: `client` unter „The client's page“, `project` unter „Create a project“, `password` unter „Passwords“
  - `mcp.md`: `mcp` unter „Connect“
  - README und og:image: `/screenshots/en/sites.png`.
- [ ] **Step 4: Doku zur Sprache** – `setup.md` / `de/setup.md`: Abschnitt „Language“ / „Sprache“: Admin und Kundenseiten auf Englisch und Deutsch, folgt der Browsersprache, Umschalter im Header, Wahl wird im Cookie gemerkt. `upgrading.md` 0.4 (EN/DE): ein Punkt dazu und „MCP error messages are now English“.
- [ ] **Step 5:** `php artisan test --filter=DocsTest && (cd docs && npm run build)` → grün, keine toten Links.
- [ ] **Step 6:** CLAUDE.md: Konvention „UI-Texte … sind Deutsch“ ersetzen durch die i18n-Regel (Schlüssel, `lang/*.json`, `t()`/`__()`, Test). Screenshot-Pfade aktualisieren. DESIGN.md: Entscheidung Sprachumschalter.
- [ ] **Step 7:** Commit „Localized screenshots and language docs“.

### Task 4: Doku unter /en und /de

**Files:** `git mv docs/{index,setup,reverse-proxy,uploading,projects,what-works,mcp,upgrading,imprint,privacy}.md docs/en/`. Modify `docs/.vitepress/config.ts`, `docs/.vitepress/theme/index.ts`, `app/Mcp/Tools/GetCompatibility.php` (`docs/en/what-works.md`), `tests/Feature/DocsTest.php` (`docs/en` statt `docs`), `tests/Feature/McpTest.php` (Pfad), alle internen Links in `docs/en/*.md` (`/setup` → `/en/setup`, Bilder bleiben `/screenshots/en/…`), README-Links (`https://prevju.dev/en/…`), CLAUDE.md.

- [ ] **Step 1:** Dateien verschieben, `config.ts`: Locale `en` mit `link: '/en/'` und `lang: 'en'` statt `root`, Sidebar-Präfix `/en`, Nav-Links `/en/setup`, Footer-Links `/en/imprint`, `/en/privacy`, `llmstxt({ ignoreFiles: ['de/**', 'en/imprint.md', 'en/privacy.md'] })`, `og:image` `https://prevju.dev/screenshots/en/sites.png`.
- [ ] **Step 2: Weiterleitung** `theme/index.ts`:

```ts
import DefaultTheme from 'vitepress/theme';
import { inBrowser } from 'vitepress';
// …imports wie bisher…

// pages moved under /en: old links (/setup, /) land there; runs on the 404 page of GitHub Pages too
if (inBrowser && !/^\/(en|de)(\/|$)/.test(location.pathname)) {
    location.replace(`/en${location.pathname === '/' ? '/' : location.pathname}${location.search}${location.hash}`);
}

export default DefaultTheme;
```

Statische Dateien (`/screenshots/…`, `/llms.txt`, `/logo.svg`) liefert GitHub Pages direkt aus, der Hook greift dort nicht.
- [ ] **Step 3:** `php artisan test` (DocsTest, MCP-Kompatibilität), `cd docs && npm run build` ohne tote Links; im Dev-Server `/`, `/setup`, `/en/projects`, `/de/projects` prüfen.
- [ ] **Step 4:** CLAUDE.md (Doku-Pfade, `get-compatibility` liest `docs/en/what-works.md`), Commit „Move English docs under /en“.

### Task 5: Abschluss

- [ ] `vendor/bin/pint --test && npx tsc -p . && php artisan test`, Identität aller Branch-Commits prüfen, Demo-Daten und Server aufräumen, finaler Review über die neuen Commits.
