<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List all projects, newest change first. A project bundles several sites (e.g. versions of a draft) behind one client url and one optional password.')]
#[IsReadOnly]
class ListProjects extends Tool
{
    public function handle(): Response
    {
        return Response::json(Project::latest('updated_at')->get()->map->summary());
    }
}
