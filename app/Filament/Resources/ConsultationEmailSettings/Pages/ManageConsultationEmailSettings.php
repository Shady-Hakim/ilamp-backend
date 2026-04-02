<?php

namespace App\Filament\Resources\ConsultationEmailSettings\Pages;

use App\Filament\Resources\ConsultationEmailSettings\ConsultationEmailSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsultationEmailSettings extends ManageRecords
{
    protected static string $resource = ConsultationEmailSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
