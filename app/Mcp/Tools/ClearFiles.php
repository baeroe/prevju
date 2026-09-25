<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove all files of a site but keep the site, its url and password. Cannot be undone. To publish a new version use write-files or get-upload-url with replace=true instead, that never leaves the site empty.')]
#[IsDestructive]
class ClearFiles extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $site->clearFiles();

        return Response::json($site->summary());
    }

    public function schema(JsonSchema $schema): array
    {
        return ['site_id' => $schema->integer()->required()];
    }
}
