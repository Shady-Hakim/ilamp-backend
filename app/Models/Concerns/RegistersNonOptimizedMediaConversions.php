<?php

namespace App\Models\Concerns;

trait RegistersNonOptimizedMediaConversions
{
    protected function registerDefaultNonOptimizedConversions(string $collection): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections($collection)
            ->width(150)
            ->sharpen(10)
            ->nonOptimized();

        $this->addMediaConversion('small')
            ->performOnCollections($collection)
            ->width(480)
            ->sharpen(10)
            ->nonOptimized();

        $this->addMediaConversion('large')
            ->performOnCollections($collection)
            ->width(1200)
            ->sharpen(10)
            ->nonOptimized();
    }
}
