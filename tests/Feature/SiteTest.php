<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
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

    public function test_unknown_route_without_extension_falls_back_to_index(): void
    {
        $this->makeSite();
        $response = $this->get('/s/abc123/dashboard/settings')->assertOk();
        $this->assertStringEndsWith('abc123/index.html', $response->baseResponse->getFile()->getPathname());
        $this->get('/s/abc123/missing.css')->assertNotFound();

        File::delete(storage_path('app/sites/abc123/index.html'));
        $this->get('/s/abc123/dashboard')->assertNotFound();
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

    public function test_preview_url_needs_valid_signature_and_rotates_with_password(): void
    {
        $site = $this->makeSite('secret');
        $old = $site->previewUrl();

        $this->get($old.'css/style.css')->assertOk();
        $this->get('/p/wrongsignature00000/abc123/index.html')->assertNotFound();
        $this->get(str_replace('/p/', '/p/x', $old).'../../.env')->assertNotFound();

        $site->update(['password' => Hash::make('new')]);
        $this->get($old.'index.html')->assertNotFound();
        $this->get($site->previewUrl().'index.html')->assertOk();
    }

    public function test_logged_in_admin_sees_protected_site_without_password(): void
    {
        $this->makeSite('secret');
        $this->actingAs(User::factory()->create())->get('/s/abc123/index.html')->assertOk();
    }
}
