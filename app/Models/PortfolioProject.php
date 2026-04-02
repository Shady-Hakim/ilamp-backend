<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PortfolioProject extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (PortfolioProject $project): void {
            if (blank($project->published_at)) {
                $project->published_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'results' => 'array',
            'gallery' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PortfolioCategory::class, 'portfolio_category_project')->withTimestamps();
    }
}
