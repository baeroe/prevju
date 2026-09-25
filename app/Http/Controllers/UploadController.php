<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __invoke(Request $request, Site $site)
    {
        $data = $request->validate(['file' => 'required|file|max:102400', 'path' => 'nullable|string|max:1024']);
        $file = $request->file('file');

        $site->addFile($file, $data['path'] ?? $file->getClientOriginalName(), $request->boolean('replace'));

        return response()->json($site->fresh()->summary());
    }
}
