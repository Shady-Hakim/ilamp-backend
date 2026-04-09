<?php

namespace App\Filament\Resources\TimelineItems;

use App\Filament\Resources\TimelineItems\Pages\ManageTimelineItems;
use App\Models\TimelineItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimelineItemResource extends Resource
{
    protected static ?string $model = TimelineItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('year')
                ->required()
                ->numeric()
                ->minValue(1900)
                ->maxValue(2100),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
            Toggle::make('is_published')->default(true),
            Textarea::make('description')->rows(4)->maxLength(2000)->columnSpanFull(),
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
                TextColumn::make('year'),
                TextColumn::make('title')->searchable(),
                TextColumn::make('sort_order'),
                IconColumn::make('is_published')->boolean(),
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
            'index' => ManageTimelineItems::route('/'),
        ];
    }
}
