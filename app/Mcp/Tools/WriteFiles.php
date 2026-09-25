<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TXT'
    Write text files (HTML, CSS, JS, SVG, JSON) you generated into a site. Paths are relative, folders are created.
    replace=true swaps the whole site for exactly these files in one step (use it for a new version of a draft); otherwise files are added or overwritten.
    For zips, images or other files on disk use get-upload-url instead.
    TXT)]
class WriteFiles extends SiteTool
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    public function handle(Request $request): Response
    {
        $site = $this->site($request);
        $data = $request->validate([
            'files' => 'required|array|min:1',
            'files.*.path' => 'required|string|max:1024',
            'files.*.content' => 'present|string',
            'replace' => 'boolean',
        ]);

        $files = collect($data['files'])->mapWithKeys(fn ($f) => [$f['path'] => $f['content']])->all();
        if (array_sum(array_map('strlen', $files)) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['files' => 'Zusammen mehr als 2 MB. Größere Entwürfe als ZIP über get-upload-url hochladen.']);
        }

        $site->writeFiles($files, $data['replace'] ?? false);

        return Response::json(['written' => count($files), ...$site->fresh()->summary()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema->integer()->required(),
            'files' => $schema->array()->items($schema->object([
                'path' => $schema->string()->description('Relative path, e.g. index.html or css/style.css')->required(),
                'content' => $schema->string()->required(),
            ]))->min(1)->required(),
            'replace' => $schema->boolean()->description('true: these files become the whole site, all others are removed'),
        ];
    }
}
