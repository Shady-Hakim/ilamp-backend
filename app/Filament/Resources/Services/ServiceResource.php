<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use App\Support\AutoSlug;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::WrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state): void {
                            AutoSlug::sync($get, $set, $old, $state);
                        }),
                    TextInput::make('slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->mutateStateForValidationUsing(function (mixed $state, Get $get): string {
                            return AutoSlug::resolve($state, $get('title'));
                        })
                        ->dehydrateStateUsing(function (mixed $state, Get $get): string {
                            return AutoSlug::resolve($state, $get('title'));
                        })
                        ->helperText('Optional. If left empty, it will be generated from the title when you save.'),
                    TextInput::make('icon_key')->maxLength(100),
                    Toggle::make('is_published')->default(true),
                    Textarea::make('short_description')->rows(3)->columnSpanFull(),
                    TagsInput::make('features')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Detail Page')
                ->schema([
                    TextInput::make('headline')->maxLength(255),
                    TextInput::make('subheadline')->maxLength(255),
                    Textarea::make('description')->rows(6)->maxLength(5000)->columnSpanFull(),
                    Repeater::make('benefits')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(255),
                            Textarea::make('text')->rows(2)->required()->maxLength(1000),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('process_steps')
                        ->schema([
                            TextInput::make('step')->required()->maxLength(255),
                            Textarea::make('desc')->rows(2)->required()->maxLength(1000),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('faq_items')
                        ->schema([
                            TextInput::make('q')->required()->maxLength(500),
                            Textarea::make('a')->rows(3)->required()->maxLength(2000),
                        ])
                        ->columnSpanFull(),
                ]),
            Section::make('SEO')
                ->schema([
                    TextInput::make('seo_title')->maxLength(255),
                    Textarea::make('seo_description')->rows(3)->maxLength(500),
                ])
                ->columns(2),
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
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug')->copyable(),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('updated_at')->since(),
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
            'index' => ManageServices::route('/'),
        ];
    }
}
