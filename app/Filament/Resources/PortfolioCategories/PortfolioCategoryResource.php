<?php

namespace App\Filament\Resources\PortfolioCategories;

use App\Filament\Resources\PortfolioCategories\Pages\ManagePortfolioCategories;
use App\Models\PortfolioCategory;
use App\Support\AutoSlug;
use App\Support\CategoryIconOptions;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfolioCategoryResource extends Resource
{
    protected static ?string $model = PortfolioCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state): void {
                    AutoSlug::sync($get, $set, $old, $state);
                }),
            TextInput::make('slug')
                ->unique(ignoreRecord: true)
                ->mutateStateForValidationUsing(function (mixed $state, Get $get): string {
                    return AutoSlug::resolve($state, $get('name'));
                })
                ->dehydrateStateUsing(function (mixed $state, Get $get): string {
                    return AutoSlug::resolve($state, $get('name'));
                })
                ->helperText('Optional. If left empty, it will be generated from the name when you save.'),
            Select::make('icon_key')
                ->label('Icon')
                ->options(CategoryIconOptions::withPreview())
                ->allowHtml()
                ->extraFieldWrapperAttributes(['class' => 'ilamp-icon-picker-grid'])
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => CategoryIconOptions::searchWithPreview($search))
                ->getOptionLabelUsing(fn ($value): ?string => CategoryIconOptions::previewLabel($value))
                ->native(false)
                ->placeholder('Choose an icon'),
            Toggle::make('is_published')->default(true),
            Textarea::make('description')->rows(4)->columnSpanFull(),
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
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug')->copyable(),
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
            'index' => ManagePortfolioCategories::route('/'),
        ];
    }
}
