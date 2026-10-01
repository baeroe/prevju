<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Several sites behind one client link and one optional password, e.g. the versions of a draft. */
class Project extends Model
{
    protected $fillable = ['name', 'slug', 'password'];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(fn (Project $project) => $project->slug ??= Str::lower(Str::random(10)));
    }

    /** Newest change first, the order the client sees. */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class)->latest('updated_at');
    }

    public function url(): string
    {
        return url("/project/{$this->slug}");
    }

    public function isOpenFor(Request $request): bool
    {
        return ! $this->password || $request->user() || $request->session()->get("project.{$this->id}");
    }

    /** What admin UI and MCP clients get to see: never the password hash. */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url(),
            'has_password' => filled($this->password),
            'site_count' => $this->sites()->count(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    public function adminCard(): array
    {
        return [...$this->summary(), 'sites' => $this->sites->map->adminCard()];
    }
}
