# Projekte – Implementierungsplan

> **Für ausführende Agenten:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development (empfohlen) oder superpowers:executing-plans. Schritte sind Checkboxen (`- [ ]`).

**Ziel:** Mehrere Sites lassen sich zu einem **Projekt** bündeln. Der Kunde bekommt einen Link `/project/<slug>`, sieht dort alle Entwürfe des Projekts, neueste Änderung zuerst, und schaltet mit einem Passwort alle auf einmal frei.

**Architektur:** Neue Tabelle `projects` (Name, Slug, optionales Passwort), `sites.project_id` nullable. Zugriff auf eine Site bleibt wie gehabt und wird um „Projekt mit Passwort freigeschaltet“ erweitert (`Site::isOpenFor()`). Die Kundenseite ist eine Inertia-Seite ohne Admin-Layout und nutzt `SitePreview` wieder. Im Admin erscheinen Projekte als Cards, deren Vorschau automatisch durch die Sites des Projekts blättert.

**Tech Stack:** Laravel 13, Inertia v3 + React 19 + TS, Tailwind 4, PHPUnit 12, laravel/mcp, VitePress.

**Spec:** Abstimmung mit dem Nutzer am 2026-10-01 (Kundenfeedback: „Kunde hat einen Link auf all seine Dummies, sortiert nach zuletzt aktualisiert, v1 … v10“). Entscheidungen:
1. Name im UI: **Projekt**.
2. Eine Site gehört zu höchstens **einem** Projekt.
3. Projekt-Passwort **und** Site-Passwort sind beide gültig. Die Freischaltung des Projekts öffnet alle seine Sites.
4. Einzel-Links (`/s/<slug>/`) von Sites in einem Projekt funktionieren weiter.
5. Im Admin ist ein Projekt eine Card wie eine Site. Ihre Vorschau (iframe) blättert wie ein Slider durch die Sites des Projekts.

## Global Constraints

- **Git-Identität:** Jeder Commit nur als `Rafael Haußmann <rafael.hingerl@gmail.com>`. Vor **jedem** Commit `git config user.email` prüfen, Ausgabe muss `rafael.hingerl@gmail.com` sein, sonst nicht committen. Keine lulububu-Adresse, keine lulububu-Skills (`git:create-commit` usw.).
- Commit-Nachrichten enden mit `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Arbeit auf Branch `feature/projects`, nicht auf `main`.
- UI-Texte (Admin, Kundenseite, Passwortseite) auf **Deutsch**. Doku auf **Englisch und Deutsch**, gleiche Dateinamen in `docs/` und `docs/de/`.
- `DESIGN.md` vor UI-Arbeit lesen. Tokens aus `resources/css/app.css`, keine neuen Farben. Kein „A · B“-Meta-String, keine ALL-CAPS-Mono-Labels (DESIGN.md, Abschnitt „Nie“).
- Neue Abhängigkeiten: keine.
- MCP-Tool-Namen in Beschreibungen/Instructions müssen existieren (`test_tool_names_mentioned_in_texts_exist`).
- Sandbox der Vorschau-iframes bleibt `allow-scripts` **ohne** `allow-same-origin`.
- Kommandos: `php artisan test`, `vendor/bin/pint`, `npx tsc -p .`.

## Review Focus

1. **Signierte Vorschau-URL leakt an Unbefugte:** Auf einer offenen Projektseite (ohne Projekt-Passwort) darf eine Site mit eigenem Passwort **keine** `preview_url` bekommen, sonst umgeht der Kunde ihr Passwort. → Test `test_open_project_does_not_unlock_protected_sites` (Task 2).
2. **Projekt-Freischaltung öffnet fremde Sites:** Das Session-Flag `project.<id>` darf nur Sites **dieses** Projekts öffnen und nur, wenn das Projekt ein Passwort hat. → Test `test_project_password_unlocks_all_its_protected_sites` prüft eine fremde Site (Task 2).
3. **Brute Force auf das Projekt-Passwort**, das jetzt viele Entwürfe auf einmal öffnet: Unlock-Routen drosseln, mit eigenem Prefix, damit sie sich das Limit nicht mit `/login` teilen. → Test `test_unlock_is_throttled` (Task 2).
4. **Projekt löschen löscht Sites:** Das darf nicht passieren. Sites bleiben und sind danach wieder einzeln. → Test `test_project_password_set_keep_clear_and_delete_keeps_sites` (Task 3).
5. **„Neueste zuerst“ stimmt nicht:** Ein Upload in eine Site muss Site **und** Projekt nach oben schieben. → Tests `test_changing_a_site_moves_its_project_to_the_top` (Task 1) und `test_project_page_lists_sites_newest_first` (Task 2).

---

## Dateien

| Datei | Aufgabe |
|---|---|
| `database/migrations/2026_10_01_120000_create_projects_table.php` (neu) | Tabelle `projects`, Spalte `sites.project_id` |
| `app/Models/Project.php` (neu) | Model, Slug, `sites()`, `url()`, `isOpenFor()`, `summary()`, `adminCard()` |
| `app/Models/Site.php` | `project()`, `$touches`, `isOpenFor()`, `project_id` in `summary()`, `adminCard()` |
| `app/Http/Controllers/ProjectController.php` (neu) | Kundenseite `show` + `unlock` |
| `app/Http/Controllers/SiteController.php` | Zugriff über `isOpenFor()`, Passwort-View mit Variablen |
| `resources/views/site-password.blade.php` | generisch: `$name`, `$action`, `$intro`, `$button` |
| `app/Http/Controllers/Admin/ProjectController.php` (neu) | Admin-CRUD Projekte |
| `app/Http/Controllers/Admin/SiteController.php` | Index mit Projekten, `project_id` in store/update, Projektliste in show |
| `routes/web.php` | neue Routen, Drosselung der Unlocks |
| `resources/js/types.ts` | `project_id`, `ProjectCard`, `ProjectOption`, `PublicSite` |
| `resources/js/lib/format.ts` | `siteCount()` |
| `resources/js/components/site-tile.tsx` (neu) | Site-Card, aus `sites/index.tsx` herausgezogen |
| `resources/js/components/project-preview.tsx` (neu) | blätternde Vorschau |
| `resources/js/components/link-bar.tsx` (neu) | Link-Zeile + Kopieren, aus `sites/show.tsx` herausgezogen |
| `resources/js/components/settings-forms.tsx` (neu) | `NameForm`, `PasswordForm`, aus `sites/show.tsx` herausgezogen und generisch |
| `resources/js/components/new-site-dialog.tsx` | `projectId`-Prop, zusätzlich `NewProjectDialog` |
| `resources/js/pages/sites/index.tsx` | Abschnitte Projekte + Sites ohne Projekt |
| `resources/js/pages/sites/show.tsx` | Projekt-Auswahl, Zurück-Link ins Projekt |
| `resources/js/pages/projects/show.tsx` (neu) | Admin-Projektseite |
| `resources/js/pages/projects/public.tsx` (neu) | Kundenseite |
| `app/Mcp/Tools/ListProjects.php`, `CreateProject.php` (neu); `CreateSite.php`, `UpdateSite.php`; `app/Mcp/Servers/PrevjuServer.php` | MCP |
| `tests/Feature/ProjectTest.php` (neu), `AdminTest.php`, `McpTest.php` | Tests |
| `docs/projects.md`, `docs/de/projects.md` (neu), `docs/mcp.md`, `docs/de/mcp.md`, `docs/uploading.md`, `docs/de/uploading.md`, `docs/upgrading.md`, `docs/de/upgrading.md`, `docs/.vitepress/config.ts` | Doku |
| `CLAUDE.md`, `DESIGN.md` | Projektwissen |

---

### Task 0: Branch und Identität

- [ ] **Step 1:** Branch anlegen und Identität prüfen

```bash
git switch main && git pull && git switch -c feature/projects
git config user.email   # muss rafael.hingerl@gmail.com ausgeben
git config user.name    # muss Rafael Haußmann ausgeben
```

Falls nicht: `git config user.name "Rafael Haußmann" && git config user.email "rafael.hingerl@gmail.com"`.

---

### Task 1: Datenmodell

**Files:**
- Create: `database/migrations/2026_10_01_120000_create_projects_table.php`, `app/Models/Project.php`, `tests/Feature/ProjectTest.php`
- Modify: `app/Models/Site.php`

**Interfaces:**
- Produces: `Project` mit `sites(): HasMany` (neueste zuerst), `url(): string` (= `url("/project/{slug}")`), `isOpenFor(Request): bool`, `summary(): array` (`id, name, url, has_password, site_count, updated_at`), `adminCard(): array` (`summary + sites: Site::adminCard()[]`). `Site::project(): BelongsTo`, `Site::isOpenFor(Request): bool`, `Site::adminCard(): array` (`summary + preview_url`), `Site::summary()` enthält `project_id`.

- [ ] **Step 1: Failing Test schreiben** – `tests/Feature/ProjectTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Site::all() as $site) {
            File::deleteDirectory($site->dir());
        }
        parent::tearDown();
    }

    public function test_project_gets_a_random_slug(): void
    {
        $project = Project::create(['name' => 'Bäckerei Kurz']);

        $this->assertSame(10, strlen($project->slug));
        $this->assertSame(url("/project/{$project->slug}"), $project->url());
    }

    public function test_changing_a_site_moves_its_project_to_the_top(): void
    {
        $this->travel(-2)->days();
        $project = Project::create(['name' => 'Bäckerei Kurz']);
        $site = Site::create(['name' => 'v1', 'slug' => 'projv1', 'project_id' => $project->id]);
        $this->travelBack();

        $this->assertFalse($project->fresh()->updated_at->isToday());
        $site->writeFiles(['index.html' => '<h1>v1</h1>']);
        $this->assertTrue($project->fresh()->updated_at->isToday());
    }
}
```

- [ ] **Step 2: Test laufen lassen**

Run: `php artisan test --filter=ProjectTest`
Expected: FAIL, `Class "App\Models\Project" not found`.

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // deleting a project keeps its sites, they become standalone again
        Schema::table('sites', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::dropIfExists('projects');
    }
};
```

- [ ] **Step 4: `app/Models/Project.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Several sites behind one client link and one optional password, e.g. the versions of a draft. */
class Project extends Model
{
    protected $fillable = ['name', 'slug', 'password'];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(fn (Project $project) => $project->slug ??= Str::lower(Str::random(10)));
    }

    /** Newest change first, the order the client sees. */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class)->latest('updated_at');
    }

    public function url(): string
    {
        return url("/project/{$this->slug}");
    }

    public function isOpenFor(Request $request): bool
    {
        return ! $this->password || $request->user() || $request->session()->get("project.{$this->id}");
    }

    /** What admin UI and MCP clients get to see: never the password hash. */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url(),
            'has_password' => filled($this->password),
            'site_count' => $this->sites()->count(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    public function adminCard(): array
    {
        return [...$this->summary(), 'sites' => $this->sites->map->adminCard()];
    }
}
```

- [ ] **Step 5: `app/Models/Site.php` ergänzen**

Imports dazu: `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Http\Request`.

```php
    protected $fillable = ['name', 'slug', 'password', 'project_id'];

    protected $hidden = ['password'];

    /** Every write touches the site, the site touches its project: both sort by last change. */
    protected $touches = ['project'];
```

Nach `booted()`:

```php
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Open without password, for the logged-in admin, after unlocking the site, or after unlocking its protected project. */
    public function isOpenFor(Request $request): bool
    {
        return ! $this->password
            || $request->user()
            || $request->session()->get("site.{$this->id}")
            || ($this->project?->password && $request->session()->get("project.{$this->project_id}"));
    }
```

In `summary()` nach `'has_password'`: `'project_id' => $this->project_id,`

Nach `summary()`:

```php
    /** summary() plus the signed preview URL: admin only, the URL skips the password. */
    public function adminCard(): array
    {
        return [...$this->summary(), 'preview_url' => $this->previewUrl()];
    }
```

- [ ] **Step 6: Tests laufen lassen**

Run: `php artisan test --filter=ProjectTest && php artisan test`
Expected: PASS, alle bestehenden Tests ebenfalls grün.

- [ ] **Step 7: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
vendor/bin/pint
git add database/migrations app/Models tests/Feature/ProjectTest.php
git commit -m "Add projects: model, migration, site relation

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Kundenseite und Freischaltung

**Files:**
- Create: `app/Http/Controllers/ProjectController.php`, `resources/js/pages/projects/public.tsx`
- Modify: `app/Http/Controllers/SiteController.php`, `resources/views/site-password.blade.php`, `routes/web.php`, `resources/js/types.ts`, `tests/Feature/ProjectTest.php`

**Interfaces:**
- Consumes: `Project::isOpenFor`, `Project::sites`, `Site::isOpenFor`, `Site::summary`, `Site::previewUrl`.
- Produces: Route `GET /project/{slug}` (Name `project.show`), `POST /project/{slug}` (Name `project.unlock`), Session-Flag `project.<id>`, Inertia-Seite `projects/public` mit Props `project: { name }`, `sites: PublicSite[]`. Blade-View `site-password` erwartet `$name, $action, $intro, $button`.

- [ ] **Step 1: Failing Tests** – an `ProjectTest` anhängen (Imports: `App\Models\User`, `Illuminate\Support\Facades\Hash`, `Inertia\Testing\AssertableInertia as Assert`)

```php
    private function project(?string $password = null): Project
    {
        return Project::create(['name' => 'Bäckerei Kurz', 'slug' => 'proj123', 'password' => $password ? Hash::make($password) : null]);
    }

    private function site(Project $project, string $slug, ?string $password = null): Site
    {
        $site = Site::create(['name' => $slug, 'slug' => $slug, 'project_id' => $project->id, 'password' => $password ? Hash::make($password) : null]);
        $site->writeFiles(['index.html' => "<h1>{$slug}</h1>"]);

        return $site;
    }

    public function test_project_page_lists_sites_newest_first(): void
    {
        $project = $this->project();
        $this->travel(-2)->days();
        $this->site($project, 'projv1');
        $this->travelBack();
        $new = $this->site($project, 'projv2');
        Site::create(['name' => 'Fremd', 'slug' => 'other1']);

        $this->get('/project/proj123')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('projects/public')
            ->where('project.name', 'Bäckerei Kurz')
            ->has('sites', 2)
            ->where('sites.0.name', 'projv2')
            ->where('sites.1.name', 'projv1')
            ->where('sites.0.url', $new->url())
            ->where('sites.0.preview_url', $new->previewUrl())
            ->missing('sites.0.password'));

        $this->get('/project/unknown')->assertNotFound();
    }

    public function test_project_password_unlocks_all_its_protected_sites(): void
    {
        $project = $this->project('kunde');
        $this->site($project, 'projv1', 'eigenes');
        $other = Site::create(['name' => 'Fremd', 'slug' => 'other1', 'password' => Hash::make('x')]);
        $other->writeFiles(['index.html' => 'x']);

        $this->get('/project/proj123')->assertUnauthorized()->assertSee('Bäckerei Kurz')->assertSee('Entwürfe öffnen');
        $this->get('/s/projv1/')->assertUnauthorized();
        $this->post('/project/proj123', ['password' => 'falsch'])->assertSessionHasErrors('password');
        $this->post('/project/proj123', ['password' => 'kunde'])->assertRedirect($project->url());

        $this->get('/project/proj123')->assertOk();
        $this->get('/s/projv1/index.html')->assertOk();
        $this->get('/s/other1/')->assertUnauthorized();
    }

    public function test_site_password_still_opens_a_site_in_a_project(): void
    {
        $this->project('kunde');
        $this->site(Project::first(), 'projv1', 'eigenes');

        $this->post('/s/projv1', ['password' => 'eigenes'])->assertRedirect();
        $this->get('/s/projv1/index.html')->assertOk();
        $this->get('/project/proj123')->assertUnauthorized();
    }

    public function test_open_project_does_not_unlock_protected_sites(): void
    {
        $project = $this->project();
        $locked = $this->site($project, 'projv1', 'eigenes');
        $this->travel(1)->minutes();
        $this->site($project, 'projv2');

        $this->get('/project/proj123')->assertOk()
            ->assertDontSee($locked->previewSignature())
            ->assertInertia(fn (Assert $page) => $page
                ->where('sites.0.name', 'projv2')
                ->where('sites.1.name', 'projv1')
                ->where('sites.1.preview_url', null));
        $this->get('/s/projv1/')->assertUnauthorized();
    }

    public function test_logged_in_admin_opens_protected_project(): void
    {
        $this->project('kunde');
        $this->actingAs(User::factory()->create())->get('/project/proj123')->assertOk();
    }

    public function test_unlock_is_throttled(): void
    {
        $this->project('kunde');
        foreach (range(1, 10) as $i) {
            $this->post('/project/proj123', ['password' => 'falsch']);
        }
        $this->post('/project/proj123', ['password' => 'falsch'])->assertTooManyRequests();
    }
```

- [ ] **Step 2: Tests laufen lassen**

Run: `php artisan test --filter=ProjectTest`
Expected: FAIL, 404 auf `/project/proj123`.

- [ ] **Step 3: Passwort-View generisch** – `resources/views/site-password.blade.php`: `{{ $site->name }}` (2×) durch `{{ $name }}` ersetzen, `action="{{ route('site.unlock', $site->slug) }}"` durch `action="{{ $action }}"`, den Text `Entwurf zur Ansicht` durch `{{ $intro }}` und den Button-Text `Entwurf öffnen` durch `{{ $button }}`.

- [ ] **Step 4: `app/Http/Controllers/SiteController.php`** – in `show()` den Passwort-Block ersetzen:

```php
        if (! $site->isOpenFor($request)) {
            return response()->view('site-password', [
                'name' => $site->name,
                'action' => route('site.unlock', $site->slug),
                'intro' => 'Entwurf zur Ansicht',
                'button' => 'Entwurf öffnen',
            ], 401);
        }
```

Der Kommentar „the logged-in admin opens protected sites …“ entfällt, das steht jetzt an `isOpenFor()`.

- [ ] **Step 5: `app/Http/Controllers/ProjectController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/** The client's page for a project: all its sites, newest change first. */
class ProjectController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if (! $project->isOpenFor($request)) {
            return response()->view('site-password', [
                'name' => $project->name,
                'action' => route('project.unlock', $project->slug),
                'intro' => 'Entwürfe zur Ansicht',
                'button' => 'Entwürfe öffnen',
            ], 401);
        }

        return Inertia::render('projects/public', [
            'project' => ['name' => $project->name],
            'sites' => $project->sites->map(fn (Site $site) => [
                ...$site->summary(),
                // the signed preview URL skips the site password: only for sites this viewer may open anyway
                'preview_url' => $site->setRelation('project', $project)->isOpenFor($request) ? $site->previewUrl() : null,
            ]),
        ]);
    }

    public function unlock(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if (! Hash::check($request->input('password', ''), $project->password)) {
            return back()->withErrors(['password' => 'Falsches Passwort.']);
        }

        $request->session()->put("project.{$project->id}", true);

        return redirect()->away($project->url());
    }
}
```

- [ ] **Step 6: Routen** – `routes/web.php`, Import `use App\Http\Controllers\ProjectController;`. Die bestehende Site-Unlock-Route bekommt dieselbe Drosselung:

```php
// own throttle prefix so unlock attempts don't share the /login budget
Route::post('/s/{slug}', [SiteController::class, 'unlock'])->middleware('throttle:10,1,unlock')->name('site.unlock');
Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('site.show');
Route::get('/p/{signature}/{slug}/{path?}', [SiteController::class, 'preview'])->where('path', '.*');
Route::post('/project/{slug}', [ProjectController::class, 'unlock'])->middleware('throttle:10,1,unlock')->name('project.unlock');
Route::get('/project/{slug}', [ProjectController::class, 'show'])->name('project.show');
```

- [ ] **Step 7: Typen** – `resources/js/types.ts`

```ts
export type SiteCard = {
    id: number;
    name: string;
    url: string;
    preview_url: string;
    has_password: boolean;
    project_id: number | null;
    file_count: number;
    has_html: boolean;
    updated_at: string;
};

export type SiteDetail = SiteCard & { files: string[] };

export type ProjectCard = {
    id: number;
    name: string;
    url: string;
    has_password: boolean;
    site_count: number;
    updated_at: string;
    sites: SiteCard[];
};

export type ProjectOption = { id: number; name: string };

/** A site on the client's project page; no preview where the viewer still needs the site's own password. */
export type PublicSite = Omit<SiteCard, 'preview_url'> & { preview_url: string | null };
```

- [ ] **Step 8: `resources/js/pages/projects/public.tsx`**

```tsx
import { Head } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { CropFrame } from '@/components/crop-frame';
import { SitePreview } from '@/components/site-preview';
import { timeAgo } from '@/lib/format';
import type { PublicSite } from '@/types';

export default function ProjectPublic({ project, sites }: { project: { name: string }; sites: PublicSite[] }) {
    return (
        <main className="mx-auto max-w-6xl px-6 pt-16 pb-24">
            <Head title={project.name}>
                <meta name="robots" content="noindex" />
            </Head>

            <p className="text-sm text-ink-muted">Entwürfe zur Ansicht</p>
            <h1 className="mt-1 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{project.name}</h1>

            {sites.length === 0 ? (
                <p className="mt-12 text-ink-muted">Hier liegt noch kein Entwurf.</p>
            ) : (
                <ul className="mt-12 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5">
                    {sites.map((site) => (
                        <li key={site.id} className="group min-w-0">
                            <a href={site.url} className="block">
                                <CropFrame>{site.preview_url ? <SitePreview site={{ ...site, preview_url: site.preview_url }} /> : <Locked />}</CropFrame>
                                <h2 className="mt-8 truncate font-medium group-hover:underline group-hover:underline-offset-4">{site.name}</h2>
                            </a>
                            <p className="mt-1 text-xs text-ink-muted">geändert {timeAgo(site.updated_at)}</p>
                        </li>
                    ))}
                </ul>
            )}
        </main>
    );
}

function Locked() {
    return (
        <div className="grid aspect-[16/10] place-items-center content-center gap-2 border bg-sheet text-sm text-ink-muted">
            <Lock className="size-5" aria-hidden />
            Eigenes Passwort
        </div>
    );
}
```

- [ ] **Step 9: Tests laufen lassen**

Run: `php artisan test && npx tsc -p .`
Expected: alles PASS, `SiteTest::test_password_protected_site` weiterhin grün.

- [ ] **Step 10: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
vendor/bin/pint
git add app routes resources tests
git commit -m "Add client project page with one password for all its sites

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Admin-Backend

**Files:**
- Create: `app/Http/Controllers/Admin/ProjectController.php`
- Modify: `app/Http/Controllers/Admin/SiteController.php`, `routes/web.php`, `tests/Feature/AdminTest.php`
- Stub (damit Inertia-Tests die Page-Datei finden, Inhalt in Task 4): `resources/js/pages/projects/show.tsx` mit `export default function ProjectShow() { return null; }`

**Interfaces:**
- Consumes: `Project::adminCard`, `Site::adminCard`.
- Produces: `GET /sites` Props `projects: ProjectCard[]`, `sites: SiteCard[]` (nur ohne Projekt). `GET /sites/{id}` Props `site: SiteDetail`, `projects: ProjectOption[]` (nach Name). `POST /sites` akzeptiert `project_id`. `PATCH /sites/{id}` akzeptiert `project_id` (null = raus aus dem Projekt, weglassen = bleibt). `POST /projects` (Redirect `/projects/{id}`), `GET /projects/{id}` (Inertia `projects/show`, Prop `project: ProjectCard`), `PATCH /projects/{id}` (`name`, `password`, `clear_password`), `DELETE /projects/{id}`.

- [ ] **Step 1: Failing Tests** – an `AdminTest` anhängen (Import `App\Models\Project`)

```php
    public function test_index_shows_projects_with_their_sites_and_only_loose_sites(): void
    {
        $project = Project::create(['name' => 'Bäckerei Kurz', 'password' => Hash::make('pw')]);
        Site::create(['name' => 'v1', 'slug' => 'projv1', 'project_id' => $project->id]);

        $this->get('/sites')->assertInertia(fn (Assert $page) => $page
            ->component('sites/index')
            ->has('projects', 1)
            ->where('projects.0.name', 'Bäckerei Kurz')
            ->where('projects.0.has_password', true)
            ->where('projects.0.site_count', 1)
            ->where('projects.0.sites.0.name', 'v1')
            ->missing('projects.0.password')
            ->has('sites', 1)
            ->where('sites.0.name', 'Test'));
    }

    public function test_create_project_and_site_inside_it(): void
    {
        $this->post('/projects', ['name' => 'Bäckerei', 'password' => 'kunde'])->assertRedirect();
        $project = Project::where('name', 'Bäckerei')->firstOrFail();
        $this->assertTrue(Hash::check('kunde', $project->password));

        $this->post('/sites', ['name' => 'v1', 'project_id' => $project->id])->assertRedirect();
        $this->assertSame($project->id, Site::where('name', 'v1')->value('project_id'));
        $this->post('/sites', ['name' => 'x', 'project_id' => 999])->assertSessionHasErrors('project_id');

        $this->get("/projects/{$project->id}")->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.name', 'Bäckerei')
            ->where('project.sites.0.name', 'v1'));
    }

    public function test_site_moves_into_and_out_of_a_project(): void
    {
        $project = Project::create(['name' => 'Bäckerei']);

        $this->patch("/sites/{$this->site->id}", ['project_id' => $project->id])->assertRedirect();
        $this->assertSame($project->id, $this->site->fresh()->project_id);

        $this->patch("/sites/{$this->site->id}", ['name' => 'Umbenannt'])->assertRedirect();
        $this->assertSame($project->id, $this->site->fresh()->project_id, 'omitted project_id stays');

        $this->get("/sites/{$this->site->id}")->assertInertia(fn (Assert $page) => $page
            ->where('site.project_id', $project->id)
            ->where('projects.0.name', 'Bäckerei'));

        $this->patch("/sites/{$this->site->id}", ['project_id' => null])->assertRedirect();
        $this->assertNull($this->site->fresh()->project_id);
    }

    public function test_project_password_set_keep_clear_and_delete_keeps_sites(): void
    {
        $project = Project::create(['name' => 'Bäckerei']);
        $this->site->update(['project_id' => $project->id]);

        $this->patch("/projects/{$project->id}", ['password' => 'eins'])->assertRedirect();
        $this->assertTrue(Hash::check('eins', $project->fresh()->password));
        $this->patch("/projects/{$project->id}", ['name' => 'Umbenannt'])->assertRedirect();
        $this->assertSame('Umbenannt', $project->fresh()->name);
        $this->assertTrue(Hash::check('eins', $project->fresh()->password));
        $this->patch("/projects/{$project->id}", ['clear_password' => true])->assertRedirect();
        $this->assertNull($project->fresh()->password);

        $this->delete("/projects/{$project->id}")->assertRedirect();
        $this->assertModelMissing($project);
        $this->assertModelExists($this->site);
        $this->assertNull($this->site->fresh()->project_id);
    }
```

Bestehender `test_guest_is_sent_to_login_and_can_log_in` bleibt, zusätzlich in diesem Test nach dem ersten Redirect: `$this->get('/projects/1')->assertRedirect('/login');`.

- [ ] **Step 2: Tests laufen lassen**

Run: `php artisan test --filter=AdminTest`
Expected: FAIL (404 auf `/projects`, fehlende Prop `projects`).

- [ ] **Step 3: `app/Http/Controllers/Admin/ProjectController.php`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function show(Project $project)
    {
        return Inertia::render('projects/show', ['project' => $project->adminCard()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'password' => 'nullable|string|max:255']);
        $project = Project::create([...$data, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null]);

        return redirect("/projects/{$project->id}");
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
        ]);

        if (isset($data['name'])) {
            $project->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $project->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $project->password = null;
        }
        $project->save();

        return back();
    }

    /** The sites stay, they become standalone again (nullOnDelete). */
    public function destroy(Project $project)
    {
        $project->delete();

        return back();
    }
}
```

- [ ] **Step 4: `app/Http/Controllers/Admin/SiteController.php`** (Import `App\Models\Project`)

```php
    public function index()
    {
        return Inertia::render('sites/index', [
            'projects' => Project::with('sites')->latest('updated_at')->get()->map->adminCard(),
            'sites' => Site::whereNull('project_id')->latest('updated_at')->get()->map->adminCard(),
        ]);
    }

    public function show(Site $site)
    {
        return Inertia::render('sites/show', [
            'site' => [...$site->adminCard(), 'files' => $site->fileList()],
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);
        $site = Site::create([...$data, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null]);

        return redirect("/sites/{$site->id}");
    }
```

In `update()` die Regel `'project_id' => 'sometimes|nullable|integer|exists:projects,id',` ergänzen und vor `$site->save()`:

```php
        if (array_key_exists('project_id', $data)) {
            $site->project_id = $data['project_id'];
        }
```

Die private Methode `card()` löschen, sie ist jetzt `Site::adminCard()`.

- [ ] **Step 5: Routen** – in der `auth`-Gruppe (Import `use App\Http\Controllers\Admin\ProjectController as AdminProjectController;`):

```php
    Route::post('/projects', [AdminProjectController::class, 'store']);
    Route::get('/projects/{project}', [AdminProjectController::class, 'show']);
    Route::patch('/projects/{project}', [AdminProjectController::class, 'update']);
    Route::delete('/projects/{project}', [AdminProjectController::class, 'destroy']);
```

- [ ] **Step 6: Stub-Page** `resources/js/pages/projects/show.tsx` anlegen (siehe Files).

- [ ] **Step 7: Tests laufen lassen**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
vendor/bin/pint
git add app routes resources tests
git commit -m "Add admin endpoints for projects and site assignment

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Admin-UI

**Files:**
- Create: `resources/js/components/site-tile.tsx`, `project-preview.tsx`, `link-bar.tsx`, `settings-forms.tsx`
- Modify: `resources/js/components/new-site-dialog.tsx`, `resources/js/lib/format.ts`, `resources/js/pages/sites/index.tsx`, `resources/js/pages/sites/show.tsx`, `resources/js/pages/projects/show.tsx`

**Interfaces:**
- Consumes: Props aus Task 3, Typen aus Task 2.
- Produces: `SiteTile({ site })`, `ProjectPreview({ sites })`, `LinkBar({ url, locked })`, `NameForm({ endpoint, id, name })`, `PasswordForm({ endpoint, id, url, hasPassword, openHint, lockedHint })`, `NewSiteDialog({ trigger?, projectId? })`, `NewProjectDialog()`, `siteCount(n)`.

Vor dem Start `DESIGN.md` lesen.

- [ ] **Step 1: `lib/format.ts`** ergänzen

```ts
export function siteCount(n: number): string {
    return n === 1 ? '1 Site' : `${n} Sites`;
}
```

- [ ] **Step 2: `components/site-tile.tsx`** – die Funktion `Card` aus `pages/sites/index.tsx` unverändert hierher verschieben, umbenennen in `export function SiteTile({ site }: { site: SiteCard })`, die Überschrift von `<h2>` zu `<h3>` ändern (sie steht künftig immer unter einer Abschnitts-`h2`). Imports mitnehmen (`Link`, `ExternalLink`, `Lock`, `CopyLinkIcon`, `CropFrame`, `SitePreview`, `Button`, `Tooltip`, `useIsHidden`, `fileCount`, `shortUrl`, `timeAgo`, `SiteCard`).

- [ ] **Step 3: `components/project-preview.tsx`**

```tsx
import { useEffect, useState } from 'react';
import { SitePreview } from '@/components/site-preview';
import { cn } from '@/lib/utils';
import type { SiteCard } from '@/types';

const INTERVAL = 4000;

/**
 * Project thumbnail that cycles through its drafts like a slider. Only the current slide and its two
 * neighbours are mounted, so a project with ten versions still loads at most three iframes.
 * Pauses on hover/focus (WCAG 2.2.2) and stands still with reduced motion.
 */
export function ProjectPreview({ sites }: { sites: SiteCard[] }) {
    const slides = sites.filter((s) => s.has_html);
    const count = slides.length;
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);

    useEffect(() => {
        if (count < 2 || paused || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const timer = setInterval(() => setIndex((i) => i + 1), INTERVAL);
        return () => clearInterval(timer);
    }, [count, paused]);

    if (count === 0) {
        return sites.length > 0 ? (
            <SitePreview site={sites[0]} />
        ) : (
            <div className="grid aspect-[16/10] place-items-center border bg-sheet text-sm text-ink-muted">Noch keine Sites</div>
        );
    }

    const current = index % count; // count can shrink after an Inertia reload
    const mounted = new Set([current, (current + 1) % count, (current + count - 1) % count]);

    return (
        <div
            className="relative aspect-[16/10]"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
        >
            {slides.map((site, i) =>
                mounted.has(i) ? (
                    <div
                        key={site.id}
                        className={cn('absolute inset-0 transition-opacity duration-700 motion-reduce:transition-none', i === current ? 'opacity-100' : 'opacity-0')}
                    >
                        <SitePreview site={site} />
                    </div>
                ) : null,
            )}
            {count > 1 && (
                <span className="absolute bottom-2 left-2 max-w-[calc(100%-1rem)] truncate bg-sheet/90 px-2 py-0.5 text-xs text-ink-muted">
                    {slides[current].name}
                </span>
            )}
        </div>
    );
}
```

- [ ] **Step 4: `components/link-bar.tsx`** – den Block `<div className="mt-6 flex flex-wrap items-stretch gap-2">…</div>` aus `pages/sites/show.tsx` hierher verschieben:

```tsx
import { ExternalLink, Lock } from 'lucide-react';
import { CopyLinkButton } from '@/components/copy-link';

/** The public link as a field to open, plus a copy button. */
export function LinkBar({ url, locked }: { url: string; locked: boolean }) {
    return (
        <div className="mt-6 flex flex-wrap items-stretch gap-2">
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className="flex h-10 min-w-0 flex-1 basis-72 items-center gap-2 border bg-sheet px-3 font-mono text-sm hover:border-ink"
            >
                {locked ? <Lock className="size-4 shrink-0 text-ink-muted" aria-label="Passwortgeschützt" /> : null}
                <span className="truncate">{url}</span>
                <ExternalLink className="ml-auto size-4 shrink-0 text-ink-muted" aria-hidden />
            </a>
            <CopyLinkButton url={url} />
        </div>
    );
}
```

- [ ] **Step 5: `components/settings-forms.tsx`** – `NameForm` und `PasswordForm` aus `pages/sites/show.tsx` hierher verschieben und generisch machen. `id` macht die Input-IDs eindeutig, `endpoint` ist die PATCH-URL.

```tsx
import { router, useForm } from '@inertiajs/react';
import { Lock, LockOpen } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function NameForm({ endpoint, id, name }: { endpoint: string; id: string; name: string }) {
    const form = useForm({ name });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(endpoint, { preserveScroll: true, onSuccess: () => toast('Name gespeichert') });
            }}
        >
            <Label htmlFor={`${id}-name`} className="font-medium">
                Name
            </Label>
            <div className="flex gap-2">
                <Input id={`${id}-name`} name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoComplete="off" required />
                <Button type="submit" variant="outline" loading={form.processing}>
                    Speichern
                </Button>
            </div>
            {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
        </form>
    );
}

type PasswordFormProps = { endpoint: string; id: string; url: string; hasPassword: boolean; openHint: string; lockedHint: string };

export function PasswordForm({ endpoint, id, url, hasPassword, openHint, lockedHint }: PasswordFormProps) {
    const form = useForm({ password: '' });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(endpoint, {
                    preserveScroll: true,
                    onSuccess: () => (form.reset(), toast(hasPassword ? 'Passwort geändert' : 'Passwort gesetzt')),
                });
            }}
        >
            {/* lets password managers attribute the password to this link */}
            <input type="text" autoComplete="username" value={url} readOnly hidden />
            <Label htmlFor={`${id}-password`} className="font-medium">
                Passwort
            </Label>
            <p className="flex items-center gap-2 text-sm text-ink-muted">
                {hasPassword ? <Lock className="size-4" aria-hidden /> : <LockOpen className="size-4" aria-hidden />}
                {hasPassword ? lockedHint : openHint}
            </p>
            <div className="flex gap-2">
                <Input
                    id={`${id}-password`}
                    name="password"
                    required
                    type="password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    placeholder={hasPassword ? 'Neues Passwort…' : 'Passwort festlegen…'}
                    autoComplete="new-password"
                />
                <Button type="submit" variant="outline" loading={form.processing}>
                    {hasPassword ? 'Ändern' : 'Setzen'}
                </Button>
            </div>
            {hasPassword && (
                <button
                    type="button"
                    className="-my-2 cursor-pointer justify-self-start py-2 text-sm text-ink-muted underline underline-offset-4 hover:text-danger"
                    onClick={() => router.patch(endpoint, { clear_password: true }, { preserveScroll: true, onSuccess: () => toast('Passwort entfernt') })}
                >
                    Passwort entfernen
                </button>
            )}
        </form>
    );
}
```

- [ ] **Step 6: `components/new-site-dialog.tsx`** – gemeinsamen Dialog herausziehen, zwei Wrapper:

```tsx
import { useForm } from '@inertiajs/react';
import { FolderPlus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function NewSiteDialog({ trigger, projectId }: { trigger?: React.ReactNode; projectId?: number }) {
    return (
        <CreateDialog
            trigger={
                trigger ?? (
                    <Button>
                        <Plus />
                        Neue Site
                    </Button>
                )
            }
            title="Neue Site"
            description="Dateien lädst du im nächsten Schritt hoch."
            url="/sites"
            extra={projectId ? { project_id: projectId } : {}}
            placeholder="z. B. Relaunch Bäckerei Kurz…"
            passwordHint="Ohne Passwort sieht jeder mit dem Link die Site."
            submit="Site anlegen"
        />
    );
}

export function NewProjectDialog() {
    return (
        <CreateDialog
            trigger={
                <Button variant="outline">
                    <FolderPlus />
                    Neues Projekt
                </Button>
            }
            title="Neues Projekt"
            description="Ein Link für den Kunden, der alle Sites des Projekts zeigt, neueste zuerst."
            url="/projects"
            extra={{}}
            placeholder="z. B. Bäckerei Kurz…"
            passwordHint="Ein Passwort öffnet alle Sites des Projekts. Ohne Passwort sieht jeder mit dem Link die Liste."
            submit="Projekt anlegen"
        />
    );
}

type CreateDialogProps = {
    trigger: React.ReactNode;
    title: string;
    description: string;
    url: string;
    extra: Record<string, number>;
    placeholder: string;
    passwordHint: string;
    submit: string;
};

function CreateDialog({ trigger, title, description, url, extra, placeholder, passwordHint, submit }: CreateDialogProps) {
    const form = useForm({ name: '', password: '', ...extra });

    return (
        <Dialog onOpenChange={(open) => !open && form.reset()}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <div className="grid gap-1">
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </div>
                <form
                    className="grid gap-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(url);
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder={placeholder}
                            autoComplete="off"
                            aria-invalid={!!form.errors.name}
                            autoFocus
                            required
                        />
                        {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password">
                            Passwort <span className="font-normal text-ink-muted">(optional)</span>
                        </Label>
                        <Input
                            id="new-password"
                            name="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            autoComplete="new-password"
                        />
                        <p className="text-sm text-ink-muted">{passwordHint}</p>
                    </div>
                    <Button type="submit" loading={form.processing} className="justify-self-start">
                        {submit}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
```

Falls `tsc` bei `useForm({ ...extra })` über den Typ klagt: `useForm<{ name: string; password: string; project_id?: number }>(…)` explizit setzen.

- [ ] **Step 7: `pages/sites/index.tsx`** umbauen

```tsx
import { Head, Link } from '@inertiajs/react';
import { Layers, Lock, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/app-layout';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { NewProjectDialog, NewSiteDialog } from '@/components/new-site-dialog';
import { ProjectPreview } from '@/components/project-preview';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useIsHidden } from '@/lib/delete-with-undo';
import { shortUrl, siteCount, timeAgo } from '@/lib/format';
import type { ProjectCard, SiteCard } from '@/types';

const GRID = 'mt-6 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5';

export default function SitesIndex({ projects, sites }: { projects: ProjectCard[]; sites: SiteCard[] }) {
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const matches = (x: { name: string; url: string }) => !q || x.name.toLowerCase().includes(q) || x.url.includes(q);
    const shownProjects = projects.filter(matches);
    const shownSites = sites.filter(matches);
    const total = sites.length + projects.reduce((n, p) => n + p.site_count, 0);
    const empty = projects.length === 0 && sites.length === 0;

    return (
        <AppLayout
            actions={
                !empty && (
                    <>
                        <NewProjectDialog />
                        <NewSiteDialog />
                    </>
                )
            }
        >
            <Head title="Sites" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <h1 className="flex items-baseline gap-3 text-3xl leading-none font-semibold tracking-tight">
                    Sites
                    {total > 0 && <span className="font-mono text-base font-normal text-ink-muted">{total}</span>}
                </h1>
                {projects.length + sites.length > 3 && (
                    <label className="relative w-full sm:w-64">
                        <span className="sr-only">Sites und Projekte durchsuchen</span>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden />
                        <Input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Suchen…" className="pl-9" />
                    </label>
                )}
            </div>

            {empty ? (
                <EmptyState />
            ) : shownProjects.length + shownSites.length === 0 ? (
                <p className="mt-16 text-ink-muted">
                    Nichts passt zu „{query}“.{' '}
                    <button className="cursor-pointer text-ink underline underline-offset-4" onClick={() => setQuery('')}>
                        Suche leeren
                    </button>
                </p>
            ) : (
                <>
                    {shownProjects.length > 0 && (
                        <section aria-labelledby="projects-heading" className="mt-12">
                            <h2 id="projects-heading" className="font-medium text-ink-muted">
                                Projekte
                            </h2>
                            <ul className={GRID}>
                                {shownProjects.map((project) => (
                                    <ProjectTile key={project.id} project={project} />
                                ))}
                            </ul>
                        </section>
                    )}
                    {shownSites.length > 0 && (
                        <section aria-labelledby="sites-heading" className="mt-12">
                            <h2 id="sites-heading" className={projects.length > 0 ? 'font-medium text-ink-muted' : 'sr-only'}>
                                Sites ohne Projekt
                            </h2>
                            <ul className={GRID}>
                                {shownSites.map((site) => (
                                    <SiteTile key={site.id} site={site} />
                                ))}
                            </ul>
                        </section>
                    )}
                </>
            )}
        </AppLayout>
    );
}

function ProjectTile({ project }: { project: ProjectCard }) {
    if (useIsHidden(`project:${project.id}`)) return null;

    return (
        <li className="group min-w-0">
            <Link href={`/projects/${project.id}`} className="block" aria-label={`Projekt ${project.name} öffnen`}>
                <CropFrame>
                    <ProjectPreview sites={project.sites} />
                </CropFrame>
            </Link>
            <div className="mt-8 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="truncate font-medium">
                        <Link href={`/projects/${project.id}`} className="hover:underline hover:underline-offset-4">
                            {project.name}
                        </Link>
                    </h3>
                    <p className="mt-0.5 truncate font-mono text-xs text-ink-muted">{shortUrl(project.url)}</p>
                </div>
                <div className="-mt-1.5 -mr-2 flex shrink-0">
                    <CopyLinkIcon url={project.url} />
                </div>
            </div>
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                <span className="inline-flex items-center gap-1">
                    <Layers className="size-3" aria-hidden />
                    {siteCount(project.site_count)}
                </span>
                {project.has_password && (
                    <span className="inline-flex items-center gap-1">
                        <Lock className="size-3" aria-hidden />
                        Passwort
                    </span>
                )}
                <span>geändert {timeAgo(project.updated_at)}</span>
            </p>
        </li>
    );
}
```

`EmptyState` bleibt wie bisher in der Datei.

- [ ] **Step 8: `pages/sites/show.tsx`** anpassen
  - Props: `{ site, projects }: { site: SiteDetail; projects: ProjectOption[] }`.
  - Zurück-Link: liegt die Site in einem Projekt (`const project = projects.find((p) => p.id === site.project_id)`), auf `/projects/${project.id}` mit Text `{project.name}`, sonst wie bisher `/sites` „Alle Sites“.
  - Link-Block durch `<LinkBar url={site.url} locked={site.has_password} />` ersetzen.
  - `NameForm`/`PasswordForm` lokal löschen und aus `@/components/settings-forms` nutzen:
    ```tsx
    <NameForm endpoint={`/sites/${site.id}`} id="site" name={site.name} />
    <ProjectSelect site={site} projects={projects} />
    <PasswordForm
        endpoint={`/sites/${site.id}`}
        id="site"
        url={site.url}
        hasPassword={site.has_password}
        openHint="Offen. Jeder mit dem Link sieht die Site."
        lockedHint="Geschützt. Kunden müssen das Passwort eingeben."
    />
    ```
  - Neue Komponente in der Datei:
    ```tsx
    function ProjectSelect({ site, projects }: { site: SiteDetail; projects: ProjectOption[] }) {
        if (projects.length === 0) return null;

        return (
            <section className="grid gap-3 border-t pt-8">
                <Label htmlFor="site-project" className="font-medium">
                    Projekt
                </Label>
                <p className="text-sm text-ink-muted">Kunden sehen alle Sites eines Projekts unter einem Link.</p>
                <select
                    id="site-project"
                    value={site.project_id ?? ''}
                    onChange={(e) => {
                        const value = e.target.value;
                        router.patch(
                            `/sites/${site.id}`,
                            { project_id: value ? Number(value) : null },
                            { preserveScroll: true, onSuccess: () => toast(value ? 'Projekt zugeordnet' : 'Aus dem Projekt genommen') },
                        );
                    }}
                    className="h-10 w-full min-w-0 border border-hairline bg-sheet px-3 text-base text-ink hover:border-ink-muted focus-visible:border-ink focus-visible:outline-none md:text-sm"
                >
                    <option value="">Kein Projekt</option>
                    {projects.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name}
                        </option>
                    ))}
                </select>
            </section>
        );
    }
    ```
  - Ungenutzte Imports entfernen (`useForm`, `Input`, `LockOpen`, `CopyLinkButton`, …).

- [ ] **Step 9: `pages/projects/show.tsx`** (ersetzt den Stub)

```tsx
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { AppLayout } from '@/components/app-layout';
import { LinkBar } from '@/components/link-bar';
import { NewSiteDialog } from '@/components/new-site-dialog';
import { NameForm, PasswordForm } from '@/components/settings-forms';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { deleteWithUndo } from '@/lib/delete-with-undo';
import { siteCount } from '@/lib/format';
import type { ProjectCard } from '@/types';

export default function ProjectShow({ project }: { project: ProjectCard }) {
    return (
        <AppLayout actions={<NewSiteDialog projectId={project.id} />}>
            <Head title={project.name} />

            <Link href="/sites" className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft className="size-4" aria-hidden />
                Alle Sites
            </Link>
            <p className="mt-4 text-sm text-ink-muted">Projekt</p>
            <h1 className="mt-1 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{project.name}</h1>
            <LinkBar url={project.url} locked={project.has_password} />

            <div className="mt-14 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section aria-labelledby="project-sites" className="grid content-start gap-8">
                    <h2 id="project-sites" className="flex items-baseline justify-between font-medium">
                        Sites
                        <span className="font-mono text-xs font-normal text-ink-muted">{siteCount(project.site_count)}</span>
                    </h2>
                    {project.sites.length === 0 ? (
                        <p className="text-sm text-ink-muted">
                            Noch keine Sites. Leg oben eine neue an oder ordne eine bestehende auf ihrer Seite diesem Projekt zu.
                        </p>
                    ) : (
                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-x-10 gap-y-12 px-5">
                            {project.sites.map((site) => (
                                <SiteTile key={site.id} site={site} />
                            ))}
                        </ul>
                    )}
                </section>

                <div className="grid content-start gap-12">
                    <NameForm endpoint={`/projects/${project.id}`} id="project" name={project.name} />
                    <PasswordForm
                        endpoint={`/projects/${project.id}`}
                        id="project"
                        url={project.url}
                        hasPassword={project.has_password}
                        openHint="Offen. Jeder mit dem Link sieht die Liste. Sites mit eigenem Passwort bleiben geschützt."
                        lockedHint="Geschützt. Das Passwort öffnet alle Sites des Projekts."
                    />
                    <section className="grid gap-3 border-t pt-8">
                        <h2 className="font-medium">Projekt löschen</h2>
                        <p className="text-sm text-ink-muted">Der Projekt-Link funktioniert danach nicht mehr. Die Sites bleiben erhalten und stehen wieder einzeln in der Übersicht.</p>
                        <Button
                            variant="danger"
                            className="justify-self-start"
                            onClick={() =>
                                router.visit('/sites', {
                                    onSuccess: () =>
                                        deleteWithUndo({ key: `project:${project.id}`, url: `/projects/${project.id}`, message: `„${project.name}“ gelöscht` }),
                                })
                            }
                        >
                            <Trash2 />
                            Projekt löschen
                        </Button>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
```

- [ ] **Step 10: Typecheck und Tests**

Run: `npx tsc -p . && php artisan test`
Expected: keine TS-Fehler, alle Tests PASS.

- [ ] **Step 11: Im Browser prüfen** (Skill `run` oder manuell; Vite läuft hier auf Port 5199, siehe History)
  - Demo-Daten: ein Projekt mit Passwort und 3 Sites mit unterschiedlichem HTML, eine Site ohne Projekt.
  - `/sites`: Projekt-Card blättert alle 4 s, pausiert beim Hover, Name der aktuellen Site unten links. Mit „reduzierte Bewegung“ im OS steht sie still.
  - Projekt-Seite: Sites-Grid, Name/Passwort ändern, „Neue Site“ landet im Projekt.
  - Site-Seite: Projekt-Select, Zurück-Link führt ins Projekt.
  - Inkognito: `/project/<slug>` → Passwortseite „Entwürfe öffnen“ → Liste neueste zuerst → Klick öffnet Site ohne zweites Passwort.
  - Breiten 375 px und 1280 px, kein horizontales Scrollen.
  - Projekt löschen → 5 s Rückgängig → Sites erscheinen danach unter „Sites ohne Projekt“.

- [ ] **Step 12: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
git add resources/js
git commit -m "Add project cards with cycling preview and project admin page

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: MCP

**Files:**
- Create: `app/Mcp/Tools/ListProjects.php`, `app/Mcp/Tools/CreateProject.php`
- Modify: `app/Mcp/Tools/CreateSite.php`, `app/Mcp/Tools/UpdateSite.php`, `app/Mcp/Servers/PrevjuServer.php`, `tests/Feature/McpTest.php`

**Interfaces:**
- Consumes: `Project::summary`, `Site::summary` (mit `project_id`).
- Produces: Tools `list-projects`, `create-project`; `create-site` mit `project_id`; `update-site` mit `project_id` / `clear_project`.

- [ ] **Step 1: Failing Tests** – in `McpTest` (Import `App\Models\Project`). Im Test `test_endpoint_rejects_missing_and_revoked_tokens` `assertJsonCount(10, …)` auf `12` ändern. Im Test `test_destructive_tools_are_annotated` ergänzen: `$this->assertTrue((new Tools\ListProjects)->toArray()['annotations']['readOnlyHint']);`. Neu:

```php
    public function test_create_and_list_projects(): void
    {
        PrevjuServer::tool(Tools\CreateProject::class, ['name' => 'Studio Kurz', 'password' => 'kunde'])
            ->assertOk()->assertSee(['"has_password":true', '"site_count":0']);
        $project = Project::firstOrFail();
        $this->assertTrue(Hash::check('kunde', $project->password));

        PrevjuServer::tool(Tools\ListProjects::class)
            ->assertOk()->assertSee(['Studio Kurz', $project->url()])->assertDontSee(['$2y$', 'password":"']);
        PrevjuServer::tool(Tools\CreateProject::class, [])->assertHasErrors();
    }

    public function test_sites_move_into_and_out_of_projects(): void
    {
        $project = Project::create(['name' => 'Studio Kurz']);

        PrevjuServer::tool(Tools\CreateSite::class, ['name' => 'v2', 'project_id' => $project->id])
            ->assertOk()->assertSee('"project_id":'.$project->id);
        PrevjuServer::tool(Tools\CreateSite::class, ['name' => 'v3', 'project_id' => 999])->assertHasErrors();

        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'project_id' => $project->id])->assertOk();
        $this->assertSame($project->id, $this->site->fresh()->project_id);
        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'name' => 'x'])->assertOk();
        $this->assertSame($project->id, $this->site->fresh()->project_id, 'omitted project stays');
        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'clear_project' => true])
            ->assertOk()->assertSee('"project_id":null');
    }
```

- [ ] **Step 2: Tests laufen lassen**

Run: `php artisan test --filter=McpTest`
Expected: FAIL (`Class "App\Mcp\Tools\CreateProject" not found`).

- [ ] **Step 3: `app/Mcp/Tools/ListProjects.php`**

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List all projects, newest change first. A project bundles several sites (e.g. versions of a draft) behind one client url and one optional password.')]
#[IsReadOnly]
class ListProjects extends Tool
{
    public function handle(): Response
    {
        return Response::json(Project::latest('updated_at')->get()->map->summary());
    }
}
```

- [ ] **Step 4: `app/Mcp/Tools/CreateProject.php`**

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a project: one url for the client that lists all its sites, newest change first. Put sites into it with project_id on create-site or update-site.')]
class CreateProject extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'password' => 'nullable|string|max:255']);

        $project = Project::create([
            'name' => $data['name'],
            'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
        ]);

        return Response::json($project->summary());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Shown to the client, e.g. "Bäckerei Kurz"')->required(),
            'password' => $schema->string()->description('Optional. Unlocks the project page and all its sites at once.'),
        ];
    }
}
```

- [ ] **Step 5: `CreateSite`** – Regel `'project_id' => 'nullable|integer|exists:projects,id'`, beim Anlegen `'project_id' => $data['project_id'] ?? null`, Schema:

```php
            'project_id' => $schema->integer()->description('Optional. Put the site into this project (see list-projects).'),
```

Die Beschreibung behält `get-compatibility`.

- [ ] **Step 6: `UpdateSite`** – Beschreibung: `'Rename a site, set or remove its password, or move it into or out of a project. Omitted fields stay as they are.'`. Regeln ergänzen: `'project_id' => 'nullable|integer|exists:projects,id', 'clear_project' => 'boolean'`. Vor `$site->save()`:

```php
        if (filled($data['project_id'] ?? null)) {
            $site->project_id = $data['project_id'];
        } elseif ($data['clear_project'] ?? false) {
            $site->project_id = null;
        }
```

Schema:

```php
            'project_id' => $schema->integer()->description('Move the site into this project'),
            'clear_project' => $schema->boolean()->description('true takes the site out of its project'),
```

- [ ] **Step 7: `PrevjuServer`** – Tools nach `UpdateSite` einfügen: `Tools\ListProjects::class, Tools\CreateProject::class,`. In den Instructions nach der `replace=true`-Zeile:

```
    To let the client compare versions instead, create each version as its own site in one project (list-projects, create-project, project_id on create-site) and give the user the project's url: the client sees all versions there, newest first.
```

- [ ] **Step 8: Tests laufen lassen**

Run: `php artisan test`
Expected: PASS, inklusive `test_tool_names_mentioned_in_texts_exist`.

- [ ] **Step 9: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
vendor/bin/pint
git add app tests
git commit -m "Add projects to MCP: list-projects, create-project, project_id on sites

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Doku und Projektwissen

**Files:**
- Create: `docs/projects.md`, `docs/de/projects.md`
- Modify: `docs/.vitepress/config.ts`, `docs/uploading.md`, `docs/de/uploading.md`, `docs/mcp.md`, `docs/de/mcp.md`, `docs/upgrading.md`, `docs/de/upgrading.md`, `CLAUDE.md`, `DESIGN.md`

- [ ] **Step 1: `docs/projects.md`**

```md
# Projects

A project bundles several sites behind one link. Send your client the project link instead of a link per draft: they see all drafts of the project on one page, the most recently changed first. Handy when you iterate: v1, v2, … v10 stay side by side and the client can say "v3 was better".

## Create a project

In the admin, click **New project**. Then either create sites right inside it (**New site** on the project page) or open an existing site and pick the project under **Project**. A site belongs to at most one project.

## The client's page

The project link (`/project/<slug>`) shows a card with a live preview for each site, sorted by last change. A click opens the site.

## Passwords

- **Project password:** one password opens the project page and every site in it, also sites that have their own password.
- **Site password:** still works. A site's own link keeps working, with or without a project.
- **Project without password:** anyone with the link sees the list. Sites with their own password show no preview there and still ask for their password.

## Deleting

Deleting a project removes only the project and its link. Its sites stay and show up as standalone sites again.

## With an agent

Over [MCP](/mcp), agents use `create-project`, `list-projects` and `project_id` on `create-site` to put each version into the project.
```

`docs/de/projects.md` als deutsche Fassung mit gleicher Struktur:

```md
# Projekte

Ein Projekt bündelt mehrere Sites hinter einem Link. Statt eines Links pro Entwurf schickst du dem Kunden den Projekt-Link: Er sieht alle Entwürfe des Projekts auf einer Seite, die zuletzt geänderten zuerst. Praktisch, wenn du iterierst: v1, v2, … v10 bleiben nebeneinander stehen, und der Kunde kann sagen „v3 war besser“.

## Projekt anlegen

Im Admin auf **Neues Projekt** klicken. Danach Sites direkt im Projekt anlegen (**Neue Site** auf der Projektseite) oder eine bestehende Site öffnen und unter **Projekt** das Projekt wählen. Eine Site gehört zu höchstens einem Projekt.

## Die Seite für den Kunden

Der Projekt-Link (`/project/<slug>`) zeigt für jede Site eine Card mit Live-Vorschau, sortiert nach letzter Änderung. Ein Klick öffnet die Site.

## Passwörter

- **Projekt-Passwort:** Ein Passwort öffnet die Projektseite und alle Sites darin, auch Sites mit eigenem Passwort.
- **Site-Passwort:** gilt weiter. Der Einzel-Link einer Site funktioniert weiter, mit oder ohne Projekt.
- **Projekt ohne Passwort:** Jeder mit dem Link sieht die Liste. Sites mit eigenem Passwort zeigen dort keine Vorschau und fragen weiter nach ihrem Passwort.

## Löschen

Ein gelöschtes Projekt entfernt nur das Projekt und seinen Link. Die Sites bleiben und stehen wieder einzeln in der Übersicht.

## Mit einem Agenten

Über [MCP](/de/mcp) legen Agenten mit `create-project`, `list-projects` und `project_id` bei `create-site` jede Version im Projekt ab.
```

- [ ] **Step 2: Sidebar** – `docs/.vitepress/config.ts`: in `pages()` `'projects'` nach `'uploading'` einfügen; in `en` `projects: 'Projects'`, in `de` `projects: 'Projekte'`.

- [ ] **Step 3: `uploading.md`** (EN/DE) – unter „Passwords“/„Passwörter“ einen Satz anhängen: EN `Several drafts for the same client? Put them into a [project](/projects): one link, one password.` / DE `Mehrere Entwürfe für denselben Kunden? Leg sie in ein [Projekt](/de/projects): ein Link, ein Passwort.`

- [ ] **Step 4: `mcp.md`** (EN/DE) – Tabellenzeilen ergänzen:
  - EN: `| list-projects, create-project | Projects: one client link for several sites |` (Toolnamen in Backticks wie die anderen Zeilen) und bei `create-site`/`update-site` „, optionally in a project“ bzw. „, move into or out of a project“.
  - DE entsprechend: „Projekte: ein Kunden-Link für mehrere Sites“, „optional in einem Projekt“, „in ein Projekt verschieben oder herausnehmen“.
  - Unter dem `replace`-Absatz: EN `To keep versions side by side for the client, agents create each version as its own site in a [project](/projects).` / DE `Sollen Versionen für den Kunden nebeneinander stehen, legen Agenten jede Version als eigene Site in einem [Projekt](/de/projects) an.`

- [ ] **Step 5: `upgrading.md`** (EN/DE), oberhalb von `### 0.3.2`:

```md
### 0.4

- New: [projects](/projects). One client link and one password for several sites, newest first. The database migrates on start, nothing to change on upgrade. Connected MCP clients see the new tools after a reconnect.
- Password pages now allow 10 attempts per minute and IP.
```

DE:

```md
### 0.4

- Neu: [Projekte](/de/projects). Ein Kunden-Link und ein Passwort für mehrere Sites, neueste zuerst. Die Datenbank migriert beim Start, beim Update ist nichts zu tun. Verbundene MCP-Clients sehen die neuen Tools nach einem Reconnect.
- Passwortseiten erlauben jetzt 10 Versuche pro Minute und IP.
```

- [ ] **Step 6: `CLAUDE.md`** aktualisieren
  - „Was prevju ist“: Satz ergänzen „Sites lassen sich zu Projekten bündeln: ein Link `/project/<slug>/` mit allen Sites, neueste zuerst, ein Passwort für alle.“
  - Architektur: Absatz zu `app/Models/Project.php` (Slug, `sites()` neueste zuerst, `isOpenFor()`), `Site::isOpenFor()` als einzige Zugriffsregel (Site-Passwort, Admin, Session `site.<id>`, oder Projekt mit Passwort + Session `project.<id>`), `$touches` am Site-Model, Kundenseite `pages/projects/public.tsx` (Inertia ohne Admin-Layout, `preview_url` nur für Sites, die der Betrachter öffnen darf), `components/project-preview.tsx` (max. 3 iframes gemountet).
  - MCP: „12 Tools“.
  - Routen: `/projects…` (auth), `GET/POST /project/{slug}` (Kundenseite/Unlock), Unlock-Routen gedrosselt mit `throttle:10,1,unlock`.
  - Doku-Seitenliste: `projects` ergänzen.

- [ ] **Step 7: `DESIGN.md`** – unter „Entscheidungen“: `- 2026-10-01: Projekte als Cards wie Sites; die Vorschau blättert alle 4 s durch die Sites des Projekts (Nutzerwunsch). Pausiert bei Hover/Fokus, steht bei reduzierter Bewegung still.`

- [ ] **Step 8: Prüfen**

Run: `php artisan test --filter=DocsTest && (cd docs && npm run build)`
Expected: PASS, VitePress-Build ohne tote Links.

- [ ] **Step 9: Commit**

```bash
git config user.email   # rafael.hingerl@gmail.com?
git add docs CLAUDE.md DESIGN.md
git commit -m "Document projects (en/de), upgrading notes for 0.4

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Abschluss

- [ ] **Step 1:** `vendor/bin/pint --test && npx tsc -p . && php artisan test`. Alles grün.
- [ ] **Step 2:** Alle Commits des Branches auf Identität prüfen: `git log main..HEAD --format='%an <%ae>' | sort -u` → nur `Rafael Haußmann <rafael.hingerl@gmail.com>`.
- [ ] **Step 3:** Push und PR (`gh pr create`, Body endet mit `🤖 Generated with [Claude Code](https://claude.com/claude-code)`). Release-Tag `v0.4.0` erst nach Merge und nur auf Ansage des Nutzers.
- [ ] **Step 4:** Session-Notiz mit dem Skill `history-doc`.
