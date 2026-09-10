<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(?string $password = null): Site
    {
        $site = Site::create(['name' => 'Test', 'slug' => 'abc123', 'password' => $password ? Hash::make($password) : null]);
        File::ensureDirectoryExists($site->dir().'/css');
        File::put($site->dir().'/index.html', '<h1>hi</h1>');
        File::put($site->dir().'/css/style.css', 'h1{}');

        return $site;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/sites/abc123'));
        parent::tearDown();
    }

    public function test_open_site_serves_files_with_mime(): void
    {
        $this->makeSite();
        $this->get('/s/abc123/index.html')->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $this->get('/s/abc123/css/style.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');
        $this->get('/s/abc123')->assertRedirect(url('/s/abc123').'/');
        $this->get('/s/abc123/nope.html')->assertNotFound();
        $this->get('/s/unknown/')->assertNotFound();
    }

    public function test_path_traversal_is_blocked(): void
    {
        $this->makeSite();
        $this->get('/s/abc123/../../.env')->assertNotFound();
        $this->get('/s/abc123/%2e%2e/%2e%2e/.env')->assertNotFound();
    }

    public function test_password_protected_site(): void
    {
        $this->makeSite('secret');
        $this->get('/s/abc123/')->assertUnauthorized()->assertSee('Passwort');
        $this->post('/s/abc123', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->get('/s/abc123/')->assertUnauthorized();
        $this->post('/s/abc123', ['password' => 'secret'])->assertRedirect();
        $this->get('/s/abc123/index.html')->assertOk();
    }

    public function test_zip_is_extracted_and_wrapping_folder_dropped(): void
    {
        $site = Site::create(['name' => 'Zip', 'slug' => 'abc123']);
        File::ensureDirectoryExists($site->dir());
        $zip = new \ZipArchive;
        $zip->open($site->dir().'/upload.zip', \ZipArchive::CREATE);
        $zip->addFromString('wrapper/index.html', '<h1>zipped</h1>');
        $zip->addFromString('wrapper/js/app.js', '1');
        $zip->addFromString('__MACOSX/._index.html', 'junk');
        $zip->close();

        $site->update(['files' => ['abc123/upload.zip']]);

        $this->assertFileExists($site->dir().'/index.html');
        $this->assertFileExists($site->dir().'/js/app.js');
        $this->assertFileDoesNotExist($site->dir().'/upload.zip');
        $this->assertFileDoesNotExist($site->dir().'/__MACOSX');
        $this->assertSame([], $site->fresh()->files);
        $this->get('/s/abc123/index.html')->assertOk();
    }
}
