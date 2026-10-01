<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('What kind of draft works on prevju (static HTML, relative paths, SPA routing, build settings for Vite/CRA). Call this before uploading a project and check the project against it.')]
#[IsReadOnly]
class GetCompatibility extends Tool
{
    public function handle(): Response
    {
        return Response::text(
            "Check the draft against this before uploading. Fix what doesn't fit (e.g. make paths relative, set the Vite base) "
            ."or tell the user what won't work. Sites are served at ".url('/s')."/<slug>/.\n\n"
            .self::section()
        );
    }

    /** The "What works" docs page (prevju.dev/en/what-works), so docs and agents read the same source. */
    public static function section(): string
    {
        return trim(file_get_contents(base_path('docs/en/what-works.md')));
    }
}
