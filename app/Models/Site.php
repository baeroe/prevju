<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class Site extends Model
{
    protected $fillable = ['name', 'slug', 'password', 'files'];

    protected $casts = ['files' => 'array'];

    protected static function booted(): void
    {
        static::saved(function (Site $site) {
            $site->extractZips();
            $site->pruneRemovedFiles();
        });
        static::deleting(fn (Site $site) => File::deleteDirectory($site->dir()));
    }

    public function dir(): string
    {
        return Storage::disk('sites')->path($this->slug);
    }

    public function url(): string
    {
        return url("/s/{$this->slug}").'/';
    }

    /** Relative paths of all files on disk, e.g. "css/style.css". */
    public function fileList(): Collection
    {
        $dir = $this->dir();

        return collect(is_dir($dir) ? File::allFiles($dir) : [])
            ->map(fn ($f) => str_replace(DIRECTORY_SEPARATOR, '/', $f->getRelativePathname()))
            ->sort()
            ->values();
    }

    /** Unpack uploaded zips into the site dir, dropping a single wrapping folder if present. */
    public function extractZips(): void
    {
        $extracted = false;

        foreach (File::glob($this->dir().'/*.zip') as $zipPath) {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                continue;
            }

            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_contains($name, '..') || str_starts_with($name, '__MACOSX') || str_ends_with($name, '/')) {
                    continue;
                }
                $names[] = $name;
            }

            $prefix = self::commonFolder($names);

            foreach ($names as $name) {
                $target = $this->dir().'/'.substr($name, strlen($prefix));
                File::ensureDirectoryExists(dirname($target));
                copy("zip://{$zipPath}#{$name}", $target);
            }

            $zip->close();
            File::delete($zipPath);
            $extracted = true;
        }

        if ($extracted) {
            // keep the upload field in sync with what is really on disk, so single files can be removed there
            $this->files = $this->fileList()->map(fn ($f) => "{$this->slug}/{$f}")->all();
            // direct update: inside the saved event the dirty check compares against stale originals
            $this->newQuery()->whereKey($this->getKey())->update(['files' => json_encode($this->files)]);
        }
    }

    /** Files removed in the upload field are only dropped from `files`; delete them from disk too. */
    public function pruneRemovedFiles(): void
    {
        if ($this->files === null) {
            return;
        }

        $keep = collect($this->files)->map(fn ($f) => substr($f, strlen($this->slug) + 1));

        $this->fileList()->diff($keep)->each(fn ($f) => File::delete($this->dir().'/'.$f));
    }

    private static function commonFolder(array $names): string
    {
        $tops = collect($names)->map(fn ($n) => str_contains($n, '/') ? strtok($n, '/').'/' : '')->unique();

        return $tops->count() === 1 && $tops->first() !== '' ? $tops->first() : '';
    }
}
