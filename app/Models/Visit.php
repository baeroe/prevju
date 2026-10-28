<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A page view or password attempt by a client. No IP, no cookie: visitor is a hash that changes daily. */
class Visit extends Model
{
    public const VIEW = 'view';

    public const UNLOCK = 'unlock';

    public const UNLOCK_FAILED = 'unlock_failed';

    public const UPDATED_AT = null;

    protected $fillable = ['site_id', 'project_id', 'kind', 'path', 'visitor', 'device', 'browser', 'os', 'referrer'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeViews(Builder $query): void
    {
        $query->where('kind', self::VIEW);
    }

    /** Visits of the project's client page plus those of its sites. */
    public function scopeForProject(Builder $query, Project|int $project): void
    {
        $id = $project instanceof Project ? $project->id : $project;

        $query->where(fn (Builder $q) => $q
            ->where('project_id', $id)
            ->orWhereIn('site_id', Site::where('project_id', $id)->select('id')));
    }

    public function summary(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'site_id' => $this->site_id,
            'project_id' => $this->project_id,
            'path' => $this->path,
            'visitor' => $this->visitor,
            'device' => $this->device,
            'browser' => $this->browser,
            'os' => $this->os,
            'referrer' => $this->referrer,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
