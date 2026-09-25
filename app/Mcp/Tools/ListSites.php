<?php

namespace App\Mcp\Tools;

use App\Models\Site;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List all sites, newest change first, with their public url, password status and file count.')]
#[IsReadOnly]
class ListSites extends Tool
{
    public function handle(): Response
    {
        return Response::json(Site::latest('updated_at')->get()->map->summary());
    }
}
