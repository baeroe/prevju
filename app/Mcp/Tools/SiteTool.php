<?php

namespace App\Mcp\Tools;

use App\Models\Site;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

/** Base for tools that act on one site given as site_id. */
abstract class SiteTool extends Tool
{
    protected function site(Request $request): Site
    {
        $id = $request->validate(['site_id' => 'required|integer'])['site_id'];

        return Site::find($id) ?? throw ValidationException::withMessages(['site_id' => "Keine Site mit ID {$id}."]);
    }
}
