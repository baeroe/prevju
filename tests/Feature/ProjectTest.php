<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Site::all() as $site) {
            File::deleteDirectory($site->dir());
        }
        parent::tearDown();
    }

    public function test_project_gets_a_random_slug(): void
    {
        $project = Project::create(['name' => 'Bäckerei Kurz']);

        $this->assertSame(10, strlen($project->slug));
        $this->assertSame(url("/project/{$project->slug}"), $project->url());
    }

    public function test_changing_a_site_moves_its_project_to_the_top(): void
    {
        $this->travel(-2)->days();
        $project = Project::create(['name' => 'Bäckerei Kurz']);
        $site = Site::create(['name' => 'v1', 'slug' => 'projv1', 'project_id' => $project->id]);
        $this->travelBack();

        $this->assertFalse($project->fresh()->updated_at->isToday());
        $site->writeFiles(['index.html' => '<h1>v1</h1>']);
        $this->assertTrue($project->fresh()->updated_at->isToday());
    }
}
