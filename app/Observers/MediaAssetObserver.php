<?php

namespace App\Observers;

use App\Models\MediaAsset;
use App\Services\MediaLibraryService;

class MediaAssetObserver
{
    public function deleted(MediaAsset $asset): void
    {
        $service = app(MediaLibraryService::class);

        $service->deletePhysicalFile($asset);
    }
}
