<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->site = Site::create(['name' => 'Test', 'slug' => 'abc123']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/sites/abc123'));
        parent::tearDown();
    }

    private function upload(string $path, string $content = 'x', ?UploadedFile $file = null)
    {
        return $this->post("/sites/{$this->site->id}/files", [
            'file' => $file ?? UploadedFile::fake()->createWithContent(basename($path), $content),
            'path' => $path,
        ], ['Accept' => 'application/json']); // like lib/upload.ts
    }

    public function test_guest_is_sent_to_login_and_can_log_in(): void
    {
        auth()->logout();
        $user = User::factory()->create(['email' => 'a@b.de', 'password' => Hash::make('secret123')]);

        $this->get('/sites')->assertRedirect('/login');
        $this->post('/login', ['email' => 'a@b.de', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@b.de', 'password' => 'secret123'])->assertRedirect('/sites');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->get('/projects/1')->assertRedirect('/login');
    }

    public function test_urls_are_https_behind_a_private_reverse_proxy(): void
    {
        auth()->logout();

        $viaProxy = $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
            ->withHeader('X-Forwarded-Proto', 'https')->get('/sites');
        $this->assertStringStartsWith('https://', $viaProxy->headers->get('Location'));
    }

    public function test_public_client_cannot_claim_https_via_header(): void
    {
        auth()->logout();

        $direct = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('X-Forwarded-Proto', 'https')->get('/sites');
        $this->assertStringStartsWith('http://', $direct->headers->get('Location'));
    }

    public function test_index_lists_sites_without_password_hash(): void
    {
        $this->site->update(['password' => Hash::make('pw')]);

        $this->get('/sites')->assertInertia(fn (Assert $page) => $page
            ->component('sites/index')
            ->has('sites', 1)
            ->where('sites.0.name', 'Test')
            ->where('sites.0.has_password', true)
            ->missing('sites.0.password'));
    }

    public function test_create_site_hashes_password_and_generates_slug(): void
    {
        $this->post('/sites', ['name' => 'Neu', 'password' => 'geheim'])->assertRedirect();

        $site = Site::where('name', 'Neu')->firstOrFail();
        $this->assertSame(10, strlen($site->slug));
        $this->assertTrue(Hash::check('geheim', $site->password));
        File::deleteDirectory($site->dir());
    }

    public function test_upload_keeps_folder_paths(): void
    {
        $this->upload('index.html', '<h1>hi</h1>')->assertNoContent();
        $this->upload('css/style.css', 'h1{}')->assertNoContent();

        $this->assertSame(['css/style.css', 'index.html'], $this->site->fileList()->all());
        $this->get('/s/abc123/css/style.css')->assertOk();
    }

    public function test_upload_rejects_path_traversal(): void
    {
        $this->upload('../evil.html')->assertStatus(422);
        $this->upload('css/../../evil.html')->assertStatus(422);
        $this->assertFileDoesNotExist(storage_path('app/sites/evil.html'));
        $this->assertFileDoesNotExist(storage_path('app/evil.html'));
    }

    public function test_zip_is_extracted_and_wrapping_folder_dropped(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('wrapper/index.html', '<h1>zipped</h1>');
        $zip->addFromString('wrapper/js/app.js', '1');
        $zip->addFromString('__MACOSX/._index.html', 'junk');
        $zip->addFromString('wrapper/../../escape.txt', 'no');
        $zip->close();

        $this->upload('draft.zip', file: new UploadedFile($zipPath, 'draft.zip', 'application/zip', test: true))->assertNoContent();

        $this->assertSame(['index.html', 'js/app.js'], $this->site->fileList()->all());
        $this->get('/s/abc123/index.html')->assertOk();
    }

    public function test_delete_file(): void
    {
        $this->upload('index.html');
        $this->upload('old.html');

        $this->delete("/sites/{$this->site->id}/files?path=old.html")->assertRedirect();
        $this->assertSame(['index.html'], $this->site->fileList()->all());
    }

    public function test_password_set_keep_and_clear(): void
    {
        $this->patch("/sites/{$this->site->id}", ['password' => 'eins'])->assertRedirect();
        $this->assertTrue(Hash::check('eins', $this->site->fresh()->password));

        $this->patch("/sites/{$this->site->id}", ['name' => 'Umbenannt'])->assertRedirect();
        $this->assertSame('Umbenannt', $this->site->fresh()->name);
        $this->assertTrue(Hash::check('eins', $this->site->fresh()->password));

        $this->patch("/sites/{$this->site->id}", ['clear_password' => true])->assertRedirect();
        $this->assertNull($this->site->fresh()->password);
    }

    public function test_delete_site_removes_folder(): void
    {
        $this->upload('index.html');

        $this->delete("/sites/{$this->site->id}")->assertRedirect();
        $this->assertModelMissing($this->site);
        $this->assertDirectoryDoesNotExist(storage_path('app/sites/abc123'));
    }

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
}
