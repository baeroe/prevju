<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\Site;
use App\Models\Visit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Whether and when clients opened a site or project: view count, last view and the latest visits (page views and password attempts) with device and browser. Visits are anonymous; the visitor hash only groups visits of the same browser on the same day.')]
#[IsReadOnly]
class ListVisits extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'site_id' => 'required_without:project_id|integer',
            'project_id' => 'integer',
            'limit' => 'integer|min:1|max:200',
        ]);

        if (isset($data['project_id'])) {
            $target = Project::find($data['project_id']) ?? throw ValidationException::withMessages(['project_id' => "No project with ID {$data['project_id']}."]);
            $visits = Visit::forProject($target);
        } else {
            $target = Site::find($data['site_id']) ?? throw ValidationException::withMessages(['site_id' => "No site with ID {$data['site_id']}."]);
            $visits = $target->visits();
        }

        return Response::json([
            ...$target->visitStats(),
            'visits' => $visits->latest()->limit($data['limit'] ?? 50)->get()->map->summary(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema->integer()->description('The site to look at'),
            'project_id' => $schema->integer()->description('Or a project: its client page plus all its sites'),
            'limit' => $schema->integer()->description('Latest visits to return, default 50, at most 200'),
        ];
    }
}
