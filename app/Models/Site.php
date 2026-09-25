<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    /** What admin UI and MCP clients get to see: never the password hash. */
    public function summary(): array
    {
        $files = $this->fileList();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url(),
            'has_password' => filled($this->password),
            'file_count' => $files->count(),
            'has_html' => $files->contains(fn ($f) => str_ends_with($f, '.html')),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
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
    public function addFile(UploadedFile $file, string $path, bool $replace = false): void
    {
        $path = self::validPath($path);
        $this->writeInto(fn (string $dir) => $this->store($dir, $file->getRealPath(), $path), $replace);
    }

    /** @param array<string, string> $files relative path => text content */
    public function writeFiles(array $files, bool $replace = false): void
    {
        $clean = [];
        foreach ($files as $path => $content) {
            $clean[self::validPath($path)] = $content;
        }

        $this->writeInto(function (string $dir) use ($clean) {
            foreach ($clean as $path => $content) {
                File::ensureDirectoryExists(dirname("{$dir}/{$path}"));
                File::put("{$dir}/{$path}", $content);
            }
        }, $replace);
    }

    public function deleteFile(string $path): void
    {
        $path = self::cleanPath($path) ?? abort(404);
        File::delete($this->dir().'/'.$path);
        $this->touch();
    }

    public function clearFiles(): void
    {
        File::deleteDirectory($this->dir());
        $this->touch();
    }

    /** Relative path or a validation error (shown in the admin and passed on to MCP clients). */
    public static function validPath(string $path): string
    {
        return self::cleanPath($path) ?? throw ValidationException::withMessages(['path' => "Ungültiger Dateipfad: {$path}"]);
    }

    /**
     * Run $write against the site dir. With $replace it writes into a staging dir that is swapped in
     * afterwards, so the live site is never empty or half replaced, and a failed write changes nothing.
     */
    private function writeInto(callable $write, bool $replace): void
    {
        if (! $replace) {
            $write($this->dir());
            $this->touch();

            return;
        }

        $staging = $this->dir().'.new-'.Str::random(8);
        File::ensureDirectoryExists($staging);
        try {
            $write($staging);
        } catch (\Throwable $e) {
            File::deleteDirectory($staging);
            throw $e;
        }

        $old = $this->dir().'.old-'.Str::random(8);
        if (is_dir($this->dir())) {
            rename($this->dir(), $old);
        }
        rename($staging, $this->dir());
        File::deleteDirectory($old);
        $this->touch();
    }

    private function store(string $dir, string $source, string $path): void
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $this->extractZip($source, dirname($path) === '.' ? $dir : "{$dir}/".dirname($path));

            return;
        }

        File::ensureDirectoryExists(dirname("{$dir}/{$path}"));
        File::copy($source, "{$dir}/{$path}");
    }

    /** Unpack a zip into $into, dropping a single wrapping folder if present. */
    private function extractZip(string $zipPath, string $into): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw ValidationException::withMessages(['file' => 'ZIP-Datei lässt sich nicht öffnen.']);
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
            $target = "{$into}/".substr($name, strlen($prefix));
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
