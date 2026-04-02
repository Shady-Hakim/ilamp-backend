<?php

namespace App\Filament\Resources\ConsultationAvailabilityOverrides\Pages;

use App\Filament\Resources\ConsultationAvailabilityOverrides\ConsultationAvailabilityOverrideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsultationAvailabilityOverrides extends ManageRecords
{
    protected static string $resource = ConsultationAvailabilityOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
