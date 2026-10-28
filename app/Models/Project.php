<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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

    /** Validation for create and update, shared by admin and API. */
    public static function rules(bool $creating = false): array
    {
        return [
            'name' => ($creating ? 'required' : 'sometimes|required').'|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
        ];
    }

    /** Apply validated rules() data: an empty password keeps the old one. */
    public function applySettings(array $data): static
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $this->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $this->password = null;
        }

        return $this;
    }

    /** Newest change first, the order the client sees. */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class)->latest('updated_at');
    }

    /** Visits of the client page itself; Visit::forProject() adds those of the sites. */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** Views of the client page and of all its sites. */
    public function visitStats(): array
    {
        $views = Visit::forProject($this)->views();
        $last = (clone $views)->max('created_at');

        return [
            'view_count' => $views->count(),
            'last_viewed_at' => $last ? Carbon::parse($last)->toIso8601String() : null,
        ];
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
        return [...$this->summary(), ...$this->visitStats(), 'sites' => $this->sites->map->adminCard()];
    }
}
