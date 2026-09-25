<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class Site extends Model
{
    protected $fillable = ['name', 'slug', 'password'];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(fn (Site $site) => $site->slug ??= Str::lower(Str::random(10)));
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

    /**
     * Password-free URL for the admin's sandboxed preview iframes (those send no session cookie).
     * Relative asset paths inherit the prefix; the signature changes with the password.
     */
    public function previewUrl(): string
    {
        return url("/p/{$this->previewSignature()}/{$this->slug}").'/';
    }

    public function previewSignature(): string
    {
        return substr(hash_hmac('sha256', $this->slug.'|'.$this->password, config('app.key')), 0, 20);
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

    /** Store an upload at a relative path; zips are unpacked in place of being stored. */
    public function addFile(UploadedFile $file, string $path): void
    {
        $path = self::cleanPath($path) ?? abort(422, 'Ungültiger Dateipfad.');

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $this->extractZip($file->getRealPath(), dirname($path) === '.' ? '' : dirname($path).'/');
        } else {
            File::ensureDirectoryExists(dirname($this->dir().'/'.$path));
            File::copy($file->getRealPath(), $this->dir().'/'.$path);
        }

        $this->touch();
    }

    public function deleteFile(string $path): void
    {
        $path = self::cleanPath($path) ?? abort(404);
        File::delete($this->dir().'/'.$path);
        $this->touch();
    }

    /** Unpack a zip into the site dir, dropping a single wrapping folder if present. */
    private function extractZip(string $zipPath, string $into): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            abort(422, 'ZIP-Datei lässt sich nicht öffnen.');
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, '__MACOSX') || str_ends_with($name, '/') || self::cleanPath($name) === null) {
                continue;
            }
            $names[] = $name;
        }

        $prefix = self::commonFolder($names);

        foreach ($names as $name) {
            $target = $this->dir().'/'.$into.substr($name, strlen($prefix));
            File::ensureDirectoryExists(dirname($target));
            copy("zip://{$zipPath}#{$name}", $target);
        }

        $zip->close();
    }

    /** Normalized relative path, or null if it could escape the site dir. */
    private static function cleanPath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $parts = explode('/', $path);

        if ($path === '' || array_intersect($parts, ['', '.', '..'])) {
            return null;
        }

        return $path;
    }

    private static function commonFolder(array $names): string
    {
        $tops = collect($names)->map(fn ($n) => str_contains($n, '/') ? strtok($n, '/').'/' : '')->unique();

        return $tops->count() === 1 && $tops->first() !== '' ? $tops->first() : '';
    }
}
