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
