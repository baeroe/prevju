<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private array $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = ['Authorization' => 'Bearer '.User::factory()->create()->createToken('ci')->plainTextToken];
    }

    protected function tearDown(): void
    {
        foreach (Site::all() as $site) {
            File::deleteDirectory($site->dir());
        }
        parent::tearDown();
    }

    public function test_requires_a_token_and_answers_with_problem_details(): void
    {
        $this->getJson('/api/sites')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJson(['type' => 'about:blank', 'title' => 'Unauthorized', 'status' => 401]);
    }

    public function test_site_lifecycle(): void
    {
        $project = Project::create(['name' => 'Relaunch']);

        $id = $this->postJson('/api/sites', ['name' => 'Startseite', 'password' => 'geheim', 'project_id' => $project->id], $this->auth)
            ->assertCreated()
            ->assertJsonPath('data.has_password', true)
            ->assertJsonPath('data.project_id', $project->id)
            ->assertJsonPath('data.view_count', 0)
            ->assertJsonMissingPath('data.password')
            ->json('data.id');
        $this->assertTrue(Hash::check('geheim', Site::find($id)->password));

        $this->patchJson("/api/sites/{$id}", ['name' => 'Home', 'clear_password' => true, 'project_id' => null], $this->auth)
            ->assertOk()
            ->assertJsonPath('data.name', 'Home')
            ->assertJsonPath('data.has_password', false)
            ->assertJsonPath('data.project_id', null);

        $this->getJson("/api/sites/{$id}", $this->auth)->assertOk()->assertJsonPath('data.url', url('/s/'.Site::find($id)->slug).'/');
        $this->deleteJson("/api/sites/{$id}", [], $this->auth)->assertNoContent();
        $this->getJson("/api/sites/{$id}", $this->auth)->assertNotFound()->assertJsonPath('status', 404);
    }

    public function test_validation_errors_list_violations(): void
    {
        $this->postJson('/api/sites', ['project_id' => 999], $this->auth)
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonFragment(['path' => 'name'])
            ->assertJsonFragment(['path' => 'project_id']);
    }

    public function test_sites_filter_sort_and_paginate(): void
    {
        $project = Project::create(['name' => 'Relaunch']);
        Site::create(['name' => 'Alpha', 'project_id' => $project->id]);
        Site::create(['name' => 'Beta', 'project_id' => $project->id]);
        Site::create(['name' => 'Gamma']);

        $this->getJson("/api/sites?filter[project_id]={$project->id}&sort=name", $this->auth)
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Beta'])
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/sites?filter[name]=amm', $this->auth)->assertJsonPath('data.*.name', ['Gamma']);
        $this->getJson('/api/sites?sort=-name&per_page=1', $this->auth)
            ->assertJsonPath('data.*.name', ['Gamma'])
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 3);

        $this->getJson('/api/sites?filter[password]=x', $this->auth)->assertStatus(400)->assertJsonPath('status', 400);
    }

    public function test_projects_with_sites(): void
    {
        $id = $this->postJson('/api/projects', ['name' => 'Relaunch', 'password' => 'geheim'], $this->auth)
            ->assertCreated()
            ->assertJsonPath('data.has_password', true)
            ->json('data.id');
        Site::create(['name' => 'v1', 'project_id' => $id]);

        $this->getJson("/api/projects/{$id}", $this->auth)->assertJsonPath('data.sites.0.name', 'v1');
        $this->getJson('/api/projects', $this->auth)->assertJsonMissingPath('data.0.sites');
        $this->getJson('/api/projects?include=sites', $this->auth)->assertJsonPath('data.0.sites.0.name', 'v1');

        $this->patchJson("/api/projects/{$id}", ['name' => 'Relaunch 2026'], $this->auth)->assertJsonPath('data.name', 'Relaunch 2026');
        $this->deleteJson("/api/projects/{$id}", [], $this->auth)->assertNoContent();
        $this->assertNull(Site::first()->project_id);
    }

    public function test_files_upload_list_delete(): void
    {
        $site = Site::create(['name' => 'Entwurf']);

        $this->post("/api/sites/{$site->id}/files", ['file' => UploadedFile::fake()->createWithContent('index.html', '<h1>v1</h1>')], $this->auth + ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.file_count', 1);
        $this->post("/api/sites/{$site->id}/files", ['file' => UploadedFile::fake()->createWithContent('x.css', 'h1{}'), 'path' => 'css/style.css'], $this->auth)->assertOk();

        $this->getJson("/api/sites/{$site->id}/files", $this->auth)->assertExactJson(['data' => ['css/style.css', 'index.html']]);

        $this->deleteJson("/api/sites/{$site->id}/files/css/style.css", [], $this->auth)->assertNoContent();
        $this->assertSame(['index.html'], $site->fileList()->all());

        $this->post("/api/sites/{$site->id}/files", ['file' => UploadedFile::fake()->createWithContent('index.html', '<h1>v2</h1>'), 'replace' => '1'], $this->auth)->assertOk();
        $this->assertSame('<h1>v2</h1>', File::get($site->dir().'/index.html'));

        $this->deleteJson("/api/sites/{$site->id}/files", [], $this->auth)->assertNoContent();
        $this->assertSame([], $site->fileList()->all());

        $this->postJson("/api/sites/{$site->id}/files", ['path' => '../x'], $this->auth)->assertUnprocessable();
    }

    public function test_visits_filtered_by_project_kind_and_time(): void
    {
        $project = Project::create(['name' => 'Relaunch']);
        $site = Site::create(['name' => 'v1', 'project_id' => $project->id]);
        $other = Site::create(['name' => 'Anderes']);
        $visit = fn (array $attrs) => Visit::create(['kind' => Visit::VIEW, 'visitor' => 'abc', 'device' => 'desktop', ...$attrs]);
        $visit(['project_id' => $project->id]);
        $visit(['site_id' => $site->id, 'kind' => Visit::UNLOCK]);
        $visit(['site_id' => $other->id])->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->getJson("/api/visits?filter[project_id]={$project->id}", $this->auth)->assertJsonCount(2, 'data');
        $this->getJson("/api/visits?filter[project_id]={$project->id}&filter[kind]=unlock", $this->auth)->assertJsonCount(1, 'data')->assertJsonPath('data.0.site_id', $site->id);
        $this->getJson('/api/visits?filter[since]='.now()->subDay()->toDateString(), $this->auth)->assertJsonCount(2, 'data');
        $this->getJson('/api/visits?filter[until]='.now()->subDay()->toDateString(), $this->auth)->assertJsonCount(1, 'data');
        $this->getJson('/api/visits?filter[since]=gestern', $this->auth)->assertUnprocessable()->assertJsonPath('violations.0.path', 'filter.since');
        $this->getJson("/api/sites/{$site->id}", $this->auth)->assertJsonPath('data.view_count', 0);
        $this->getJson("/api/projects/{$project->id}", $this->auth)->assertJsonPath('data.view_count', 1);
    }

    public function test_rate_limit_per_token(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/visits', $this->auth)->assertOk();
        }
        $this->getJson('/api/visits', $this->auth)->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('status', 429);
    }
}
