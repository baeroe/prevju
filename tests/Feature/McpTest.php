<?php

namespace Tests\Feature;

use App\Mcp\Servers\PrevjuServer;
use App\Mcp\Tools;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Server\Attributes\Instructions;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::create(['name' => 'Kampagne Herbst', 'slug' => 'abc123', 'password' => Hash::make('geheim')]);
        $this->site->writeFiles(['index.html' => '<h1>v1</h1>', 'css/style.css' => 'h1{}']);
    }

    protected function tearDown(): void
    {
        foreach (Site::all() as $site) {
            File::deleteDirectory($site->dir());
        }
        parent::tearDown();
    }

    private function files(): array
    {
        return $this->site->fileList()->all();
    }

    // --- endpoint & auth ---

    public function test_endpoint_rejects_missing_and_revoked_tokens(): void
    {
        $call = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

        $this->postJson('/mcp', $call)->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('test');
        $this->postJson('/mcp', $call, ['Authorization' => "Bearer {$token->plainTextToken}"])
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'list-sites')
            ->assertJsonCount(9, 'result.tools');

        $token->accessToken->delete();
        auth()->forgetGuards();
        $this->postJson('/mcp', $call, ['Authorization' => "Bearer {$token->plainTextToken}"])->assertUnauthorized();
    }

    // --- read ---

    public function test_list_sites_shows_summary_without_password_hash(): void
    {
        PrevjuServer::tool(Tools\ListSites::class)
            ->assertOk()
            ->assertSee(['Kampagne Herbst', '"has_password":true', '"file_count":2', $this->site->url()])
            ->assertDontSee(['$2y$', 'password":"']);
    }

    public function test_get_site_lists_files_and_rejects_unknown_id(): void
    {
        PrevjuServer::tool(Tools\GetSite::class, ['site_id' => $this->site->id])
            ->assertOk()
            ->assertSee(['css/style.css', 'index.html']);

        PrevjuServer::tool(Tools\GetSite::class, ['site_id' => 999])->assertHasErrors(['Keine Site mit ID 999.']);
    }

    // --- create / update ---

    public function test_create_site_with_and_without_password(): void
    {
        PrevjuServer::tool(Tools\CreateSite::class, ['name' => 'Relaunch Bäckerei', 'password' => 'pw'])
            ->assertOk()->assertSee('"has_password":true');
        $this->assertTrue(Hash::check('pw', Site::where('name', 'Relaunch Bäckerei')->value('password')));

        PrevjuServer::tool(Tools\CreateSite::class, ['name' => 'Offen'])->assertOk()->assertSee('"has_password":false');
        PrevjuServer::tool(Tools\CreateSite::class, [])->assertHasErrors();
    }

    public function test_update_site_renames_and_changes_or_clears_password(): void
    {
        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'name' => 'Kampagne Winter'])->assertOk();
        $this->assertSame('Kampagne Winter', $this->site->fresh()->name);
        $this->assertTrue(Hash::check('geheim', $this->site->fresh()->password), 'omitted password stays');

        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'password' => 'neu'])->assertOk();
        $this->assertTrue(Hash::check('neu', $this->site->fresh()->password));

        PrevjuServer::tool(Tools\UpdateSite::class, ['site_id' => $this->site->id, 'clear_password' => true])
            ->assertOk()->assertSee('"has_password":false');
        $this->assertNull($this->site->fresh()->password);
    }

    // --- write-files ---

    public function test_write_files_adds_and_overwrites(): void
    {
        PrevjuServer::tool(Tools\WriteFiles::class, ['site_id' => $this->site->id, 'files' => [
            ['path' => 'index.html', 'content' => '<h1>v2</h1>'],
            ['path' => 'pages/about.html', 'content' => '<p>about</p>'],
        ]])->assertOk()->assertSee('"written":2');

        $this->assertSame(['css/style.css', 'index.html', 'pages/about.html'], $this->files());
        $this->assertSame('<h1>v2</h1>', File::get($this->site->dir().'/index.html'));
    }

    public function test_write_files_with_replace_keeps_only_the_new_files(): void
    {
        PrevjuServer::tool(Tools\WriteFiles::class, ['site_id' => $this->site->id, 'replace' => true, 'files' => [
            ['path' => 'index.html', 'content' => '<h1>v2</h1>'],
        ]])->assertOk();

        $this->assertSame(['index.html'], $this->files());
        $this->assertSame([], File::glob(storage_path('app/sites/abc123.*')), 'no staging dirs left behind');
    }

    public function test_write_files_rejects_traversal_and_oversize_without_touching_the_site(): void
    {
        PrevjuServer::tool(Tools\WriteFiles::class, ['site_id' => $this->site->id, 'replace' => true, 'files' => [
            ['path' => 'ok.html', 'content' => 'x'],
            ['path' => '../../evil.php', 'content' => 'x'],
        ]])->assertHasErrors(['Ungültiger Dateipfad: ../../evil.php']);

        PrevjuServer::tool(Tools\WriteFiles::class, ['site_id' => $this->site->id, 'files' => [
            ['path' => 'big.html', 'content' => str_repeat('x', 2 * 1024 * 1024 + 1)],
        ]])->assertHasErrors(['Zusammen mehr als 2 MB. Größere Entwürfe als ZIP über get-upload-url hochladen.']);

        $this->assertSame(['css/style.css', 'index.html'], $this->files());
        $this->assertFileDoesNotExist(storage_path('app/evil.php'));
    }

    // --- get-upload-url + signed upload route ---

    private function uploadUrl(bool $replace = false): string
    {
        $response = PrevjuServer::tool(Tools\GetUploadUrl::class, ['site_id' => $this->site->id, 'replace' => $replace])
            ->assertOk()->assertSee('curl -fsS -F');

        return $this->toolJson($response)['upload_url'];
    }

    /** Decoded JSON body of a tool's text response. */
    private function toolJson($response): array
    {
        return json_decode((fn () => $this->content()[0])->call($response), true);
    }

    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'draft.zip', 'application/zip', test: true);
    }

    public function test_upload_url_accepts_zip_and_single_files(): void
    {
        $url = $this->uploadUrl();

        $this->post($url, ['file' => $this->zip(['dist/index.html' => '<h1>zip</h1>', 'dist/js/app.js' => '1'])])
            ->assertOk()->assertJsonPath('file_count', 3);
        $this->assertSame(['css/style.css', 'index.html', 'js/app.js'], $this->files());

        $this->post($url, ['file' => UploadedFile::fake()->create('logo.png', 5), 'path' => 'img/logo.png'])->assertOk();
        $this->assertContains('img/logo.png', $this->files());
    }

    public function test_upload_url_with_replace_swaps_the_whole_site(): void
    {
        $this->post($this->uploadUrl(replace: true), ['file' => $this->zip(['index.html' => '<h1>v3</h1>'])])->assertOk();

        $this->assertSame(['index.html'], $this->files());
        $this->assertSame('<h1>v3</h1>', File::get($this->site->dir().'/index.html'));
    }

    public function test_broken_zip_with_replace_leaves_the_site_untouched(): void
    {
        $broken = UploadedFile::fake()->createWithContent('draft.zip', 'not a zip');

        $this->post($this->uploadUrl(replace: true), ['file' => $broken])->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertSame(['css/style.css', 'index.html'], $this->files());
        $this->assertSame([], File::glob(storage_path('app/sites/abc123.*')));
    }

    public function test_upload_url_rejects_tampered_expired_and_traversal(): void
    {
        $url = $this->uploadUrl();
        $file = fn () => UploadedFile::fake()->createWithContent('x.html', 'x');

        $this->post(str_replace('signature=', 'signature=0', $url), ['file' => $file()])->assertForbidden();
        $other = Site::create(['name' => 'Andere Site']);
        $this->post(str_replace("/upload/{$this->site->id}", "/upload/{$other->id}", $url), ['file' => $file()])->assertForbidden();
        $this->post($url.'&replace=1', ['file' => $file()])->assertForbidden(); // can't add replace to a plain URL
        $this->post($url, ['file' => $file(), 'path' => '../evil.html'])->assertStatus(422);

        $this->travel(16)->minutes();
        $this->post($url, ['file' => $file()])->assertForbidden();

        $this->assertSame(['css/style.css', 'index.html'], $this->files());
    }

    // --- destructive ---

    public function test_delete_file_and_unknown_file(): void
    {
        PrevjuServer::tool(Tools\DeleteFile::class, ['site_id' => $this->site->id, 'path' => 'css/style.css'])->assertOk();
        $this->assertSame(['index.html'], $this->files());

        PrevjuServer::tool(Tools\DeleteFile::class, ['site_id' => $this->site->id, 'path' => 'nope.html'])
            ->assertHasErrors(['Datei nope.html gibt es in dieser Site nicht.']);
    }

    public function test_clear_files_keeps_site_and_password(): void
    {
        PrevjuServer::tool(Tools\ClearFiles::class, ['site_id' => $this->site->id])->assertOk()->assertSee('"file_count":0');

        $this->assertSame([], $this->files());
        $this->assertNotNull($this->site->fresh()?->password);
    }

    public function test_delete_site_removes_record_and_folder(): void
    {
        PrevjuServer::tool(Tools\DeleteSite::class, ['site_id' => $this->site->id])->assertOk();

        $this->assertModelMissing($this->site);
        $this->assertDirectoryDoesNotExist(storage_path('app/sites/abc123'));
    }

    public function test_tool_names_mentioned_in_texts_exist(): void
    {
        $tools = (new \ReflectionClass(PrevjuServer::class))->getDefaultProperties()['tools'];
        $names = array_map(fn ($t) => (new $t)->name(), $tools);
        $texts = array_map(fn ($t) => (new $t)->description(), $tools);
        $texts[] = (new \ReflectionClass(PrevjuServer::class))->getAttributes(Instructions::class)[0]->getArguments()[0];

        preg_match_all('/\b(?:list|get|create|update|write|delete|clear)-[a-z-]+/', implode("\n", $texts), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertSame([], array_values(array_diff(array_unique($m[0]), $names)));
    }

    public function test_destructive_tools_are_annotated(): void
    {
        foreach ([Tools\DeleteFile::class, Tools\ClearFiles::class, Tools\DeleteSite::class] as $tool) {
            $this->assertTrue((new $tool)->toArray()['annotations']['destructiveHint'], $tool);
        }
        $this->assertTrue((new Tools\ListSites)->toArray()['annotations']['readOnlyHint']);
    }
}
