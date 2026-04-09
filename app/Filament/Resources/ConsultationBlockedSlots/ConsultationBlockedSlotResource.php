<?php

namespace App\Filament\Resources\ConsultationBlockedSlots;

use App\Filament\Resources\ConsultationBlockedSlots\Pages\ManageConsultationBlockedSlots;
use App\Models\ConsultationBlockedSlot;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationBlockedSlotResource extends Resource
{
    protected static ?string $model = ConsultationBlockedSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::NoSymbol;

    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->required(),
            TimePicker::make('start_time')->seconds(false)->required(),
            TimePicker::make('end_time')->seconds(false)->required(),
            TextInput::make('reason')->maxLength(500),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date(),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('reason'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConsultationBlockedSlots::route('/'),
        ];
    }
}
