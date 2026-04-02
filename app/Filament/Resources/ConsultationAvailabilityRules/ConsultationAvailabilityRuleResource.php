<?php

namespace App\Filament\Resources\ConsultationAvailabilityRules;

use App\Filament\Resources\ConsultationAvailabilityRules\Pages\ManageConsultationAvailabilityRules;
use App\Models\ConsultationAvailabilityRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationAvailabilityRuleResource extends Resource
{
    protected static ?string $model = ConsultationAvailabilityRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('weekday')
                ->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ])
                ->required(),
            TimePicker::make('start_time')->seconds(false)->required(),
            TimePicker::make('end_time')->seconds(false)->required(),
            TextInput::make('slot_duration_minutes')->numeric()->default(60)->required(),
            TextInput::make('buffer_minutes')->numeric()->default(0)->required(),
            Toggle::make('is_enabled')->default(true),
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
                TextColumn::make('weekday'),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('slot_duration_minutes'),
                IconColumn::make('is_enabled')->boolean(),
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
            'index' => ManageConsultationAvailabilityRules::route('/'),
        ];
    }
}
