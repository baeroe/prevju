<?php

namespace Tests\Feature;

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
        ]);
    }

    public function test_guest_is_sent_to_login_and_can_log_in(): void
    {
        auth()->logout();
        $user = User::factory()->create(['email' => 'a@b.de', 'password' => Hash::make('secret123')]);

        $this->get('/sites')->assertRedirect('/login');
        $this->post('/login', ['email' => 'a@b.de', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@b.de', 'password' => 'secret123'])->assertRedirect('/sites');
        $this->assertAuthenticatedAs($user);
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
}
