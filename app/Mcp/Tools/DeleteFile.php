<?php

namespace App\Mcp\Tools;

use App\Models\Site;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete one file of a site by its relative path. Cannot be undone.')]
#[IsDestructive]
class DeleteFile extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $path = Site::validPath($request->validate(['path' => 'required|string'])['path']);

        if (! $site->fileList()->contains($path)) {
            throw ValidationException::withMessages(['path' => "Datei {$path} gibt es in dieser Site nicht."]);
        }
        $site->deleteFile($path);

        return Response::json(['deleted' => $path]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema->integer()->required(),
            'path' => $schema->string()->description('Relative path as listed by get-site')->required(),
        ];
    }
}
