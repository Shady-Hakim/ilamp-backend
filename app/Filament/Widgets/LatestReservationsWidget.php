<?php

namespace App\Filament\Widgets;

use App\Models\ConsultationReservation;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestReservationsWidget extends TableWidget
{
    protected static ?int $sort = -7;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest Reservations')
            ->description('Most recent consultation bookings and their current status.')
            ->query(ConsultationReservation::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->description(fn (ConsultationReservation $record): string => $record->email),
                TextColumn::make('date')
                    ->date('M j, Y'),
                TextColumn::make('start_time')
                    ->time('g:i A'),
                BadgeColumn::make('status'),
                TextColumn::make('created_at')
                    ->label('Booked')
                    ->since(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->emptyStateHeading('No reservations yet')
            ->emptyStateDescription('Consultation bookings will appear here once visitors start reserving slots.');
    }
}
