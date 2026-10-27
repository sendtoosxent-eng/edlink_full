<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DesktopRelease extends Model
{
    protected $fillable = ['platform', 'version', 'path', 'filename', 'size', 'sha256', 'published_at', 'is_active'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'published_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public static function currentWindows(): ?self
    {
        if (! Schema::hasTable('desktop_releases')) {
            return null;
        }

        return static::query()->where('platform', 'windows-x64')->where('is_active', true)
            ->where('published_at', '<=', now())->orderByDesc('published_at')->orderByDesc('id')->first();
    }

    public function hasSafePath(): bool
    {
        return preg_match('/\Adesktop-releases\/[a-f0-9]{64}\/Edlink-Setup-[a-zA-Z0-9._-]+-x64\.exe\z/', $this->path) === 1;
    }

    public function sizeLabel(): string
    {
        return number_format($this->size / 1048576, 0).' MB';
    }
}
