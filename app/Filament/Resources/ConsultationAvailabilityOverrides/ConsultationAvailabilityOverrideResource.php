<?php

namespace App\Filament\Resources\ConsultationAvailabilityOverrides;

use App\Filament\Resources\ConsultationAvailabilityOverrides\Pages\ManageConsultationAvailabilityOverrides;
use App\Models\ConsultationAvailabilityOverride;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationAvailabilityOverrideResource extends Resource
{
    protected static ?string $model = ConsultationAvailabilityOverride::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->required(),
            Select::make('mode')
                ->options([
                    'add' => 'Add',
                    'replace' => 'Replace',
                    'close' => 'Close Day',
                ])
                ->required(),
            TimePicker::make('start_time')->seconds(false),
            TimePicker::make('end_time')->seconds(false),
            TextInput::make('slot_duration_minutes')->numeric()->minValue(5)->maxValue(480),
            TextInput::make('buffer_minutes')->numeric()->minValue(0)->maxValue(120),
            TextInput::make('note')->maxLength(500),
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
                TextColumn::make('mode'),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('note'),
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
            'index' => ManageConsultationAvailabilityOverrides::route('/'),
        ];
    }
}
