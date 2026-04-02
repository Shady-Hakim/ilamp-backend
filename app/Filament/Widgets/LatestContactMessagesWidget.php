<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestContactMessagesWidget extends TableWidget
{
    protected static ?int $sort = -8;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest Messages')
            ->description('Recent contact submissions that may need follow-up.')
            ->query(ContactMessage::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('Sender')
                    ->description(fn (ContactMessage $record): string => $record->email),
                TextColumn::make('subject')
                    ->placeholder('No subject')
                    ->limit(50),
                BadgeColumn::make('status'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->emptyStateHeading('No contact messages yet')
            ->emptyStateDescription('New enquiries from the website will appear here.');
    }
}
