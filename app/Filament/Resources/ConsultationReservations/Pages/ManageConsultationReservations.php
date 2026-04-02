<?php

namespace App\Filament\Resources\ConsultationReservations\Pages;

use App\Filament\Resources\ConsultationReservations\ConsultationReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsultationReservations extends ManageRecords
{
    protected static string $resource = ConsultationReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
