<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Rename a site, set or change its password, or remove the password. Omitted fields stay as they are.')]
#[IsIdempotent]
class UpdateSite extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
        ]);

        if (isset($data['name'])) {
            $site->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $site->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $site->password = null;
        }
        $site->save();

        return Response::json($site->summary());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema->integer()->required(),
            'name' => $schema->string(),
            'password' => $schema->string()->description('New password'),
            'clear_password' => $schema->boolean()->description('true removes the password, the site becomes public'),
        ];
    }
}
