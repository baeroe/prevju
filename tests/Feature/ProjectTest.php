<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
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
        $this->site($this->project('kunde'), 'projv1', 'eigenes');

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
}
