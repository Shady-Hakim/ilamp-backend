<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    public const DISK_PUBLIC = 'public';

    public const DISK_PUBLIC_PATH = 'public_path';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function getUrlAttribute(): string
    {
        if (blank($this->relative_path)) {
            return '';
        }

        if ($this->storage_disk === self::DISK_PUBLIC) {
            return (string) Storage::disk(self::DISK_PUBLIC)->url($this->relative_path);
        }

        return '/' . ltrim($this->relative_path, '/');
    }

    public function getFormattedSizeAttribute(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $size >= 10 || $unitIndex === 0 ? 0 : 1) . ' ' . $units[$unitIndex];
    }

    public function getFormattedDimensionsAttribute(): ?string
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return "{$this->width} x {$this->height}";
    }
}
