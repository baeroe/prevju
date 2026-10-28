<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index(Site $site)
    {
        return response()->json(['data' => $site->fileList()]);
    }

    /** One file or zip per request; path defaults to the file name, replace swaps the whole site in one step. */
    public function store(Request $request, Site $site)
    {
        $data = $request->validate(['file' => 'required|file|max:102400', 'path' => 'nullable|string|max:1024', 'replace' => 'boolean']);
        $file = $request->file('file');

        $site->addFile($file, $data['path'] ?? $file->getClientOriginalName(), $data['replace'] ?? false);

        return SiteResource::make($site->refresh());
    }

    public function destroy(Site $site, string $path)
    {
        $site->deleteFile($path);

        return response()->noContent();
    }

    public function clear(Site $site)
    {
        $site->clearFiles();

        return response()->noContent();
    }
}
