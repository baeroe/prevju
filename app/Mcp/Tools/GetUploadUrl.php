<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TXT'
    Get a short-lived upload URL for files on disk, typically a zip of a built draft. Upload with the returned curl command (multipart field "file").
    Zips are unpacked, a single wrapping folder is dropped. Other files land at their file name, or at the optional "path" form field.
    The URL is valid for 15 minutes and can be used for several uploads. With replace=true every upload replaces the whole site, so use it for one zip only.
    TXT)]
class GetUploadUrl extends SiteTool
{
    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $replace = (bool) ($request->validate(['replace' => 'boolean'])['replace'] ?? false);
        $expires = now()->addMinutes(15);
        $url = URL::temporarySignedRoute('upload', $expires, ['site' => $site->id, ...($replace ? ['replace' => 1] : [])]);

        return Response::json([
            'upload_url' => $url,
            'expires_at' => $expires->toIso8601String(),
            'curl' => "curl -fsS -F \"file=@<local-file>\" \"{$url}\"",
            'site_url' => $site->url(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema->integer()->required(),
            'replace' => $schema->boolean()->description('true: the upload replaces all files of the site'),
        ];
    }
}
