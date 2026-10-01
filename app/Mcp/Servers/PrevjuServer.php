<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('prevju')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
    prevju hosts static HTML drafts for clients. A "site" is a folder of files served at its `url`, optionally behind a password.
    Before uploading anything, call get-compatibility and check the project against it (relative paths, build base, SPA routing). Fix the draft or tell the user if it won't work.
    Typical flow: get-compatibility, create-site, then write-files (text you generated) or get-upload-url (a zip or binary files on disk), then give the user the site's url.
    To publish a new version of a draft, upload with replace=true instead of deleting first: the live site switches over in one step.
    To let the client compare versions instead, create each version as its own site in one project (list-projects, create-project, project_id on create-site) and give the user the project's url: the client sees all versions there, newest first.
    Sites are served below /s/<slug>/, so use relative paths inside the HTML (css/style.css, not /css/style.css).
    TXT)]
class PrevjuServer extends Server
{
    protected array $tools = [
        Tools\GetCompatibility::class,
        Tools\ListSites::class,
        Tools\GetSite::class,
        Tools\CreateSite::class,
        Tools\UpdateSite::class,
        Tools\ListProjects::class,
        Tools\CreateProject::class,
        Tools\WriteFiles::class,
        Tools\GetUploadUrl::class,
        Tools\DeleteFile::class,
        Tools\ClearFiles::class,
        Tools\DeleteSite::class,
    ];
}
