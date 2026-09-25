<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get one site with the relative paths of all its files.')]
#[IsReadOnly]
class GetSite extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);

        return Response::json([...$site->summary(), 'files' => $site->fileList()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['site_id' => $schema->integer()->required()];
    }
}
