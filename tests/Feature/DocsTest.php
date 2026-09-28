<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** The docs site exists in English (docs/) and German (docs/de/); keep both in step. */
class DocsTest extends TestCase
{
    public function test_every_page_exists_in_both_languages(): void
    {
        $names = fn (string $dir) => collect(File::files(base_path($dir)))
            ->map->getFilename()->filter(fn ($f) => str_ends_with($f, '.md'))->sort()->values()->all();

        $this->assertSame($names('docs'), $names('docs/de'));
    }

    public function test_compatibility_tables_have_the_same_verdicts(): void
    {
        // get-compatibility serves the English page to agents, the German one must not drift from it
        $verdicts = fn (string $file) => preg_match_all('/^\|[^|]+\|\s*(✅|❌|⚠️)\s*\|/mu', File::get(base_path($file)), $m) ? $m[1] : [];

        $en = $verdicts('docs/what-works.md');
        $this->assertCount(8, $en);
        $this->assertSame($en, $verdicts('docs/de/what-works.md'));
    }
}
