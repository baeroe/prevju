<?php

namespace App\Mcp\Tools;

use App\Models\Site;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create an empty site. Returns its id and the public url to send to the client. Add files with write-files or get-upload-url.')]
class CreateSite extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'password' => 'nullable|string|max:255']);

        $site = Site::create([
            'name' => $data['name'],
            'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
        ]);

        return Response::json($site->summary());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Shown in the admin and on the password page, e.g. "Relaunch Bäckerei Kurz"')->required(),
            'password' => $schema->string()->description('Optional. Clients must enter it to view the site.'),
        ];
    }
}
