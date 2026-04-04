<?php

namespace App\Models;

use App\Models\Concerns\RegistersNonOptimizedMediaConversions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PortfolioProject extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersNonOptimizedMediaConversions;

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
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PortfolioCategory::class, 'portfolio_category_project')->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('client_logo')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerDefaultNonOptimizedConversions('featured_image');
        $this->registerDefaultNonOptimizedConversions('client_logo');
        $this->registerDefaultNonOptimizedConversions('gallery');
    }

    public function getImageResolvedUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image', 'large') ?: null;
    }

    public function getClientLogoResolvedUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('client_logo', 'large') ?: null;
    }

    /**
     * @return array<int, string>
     */
    public function getGalleryResolvedUrlsAttribute(): array
    {
        $urls = $this->getMedia('gallery')
            ->map(fn (Media $media): string => $media->getUrl('large'))
            ->filter()
            ->values()
            ->all();

        return $urls;
    }
}
