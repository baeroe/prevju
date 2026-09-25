<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a site with all its files. Its url stops working. Cannot be undone.')]
#[IsDestructive]
class DeleteSite extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $site->delete();

        return Response::json(['deleted' => $site->id, 'name' => $site->name]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['site_id' => $schema->integer()->required()];
    }
}
