<?php

namespace App\Filament\Resources\ConsultationAvailabilityRules\Pages;

use App\Filament\Resources\ConsultationAvailabilityRules\ConsultationAvailabilityRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsultationAvailabilityRules extends ManageRecords
{
    protected static string $resource = ConsultationAvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
