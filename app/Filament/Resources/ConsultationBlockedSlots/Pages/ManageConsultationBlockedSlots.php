<?php

namespace App\Filament\Resources\ConsultationBlockedSlots\Pages;

use App\Filament\Resources\ConsultationBlockedSlots\ConsultationBlockedSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsultationBlockedSlots extends ManageRecords
{
    protected static string $resource = ConsultationBlockedSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
