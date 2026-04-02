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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state): void {
                            AutoSlug::sync($get, $set, $old, $state);
                        }),
                    TextInput::make('slug')
                        ->unique(ignoreRecord: true)
                        ->mutateStateForValidationUsing(function (mixed $state, Get $get): string {
                            return AutoSlug::resolve($state, $get('title'));
                        })
                        ->dehydrateStateUsing(function (mixed $state, Get $get): string {
                            return AutoSlug::resolve($state, $get('title'));
                        })
                        ->helperText('Optional. If left empty, it will be generated from the title when you save.'),
                    TextInput::make('icon_key'),
                    Toggle::make('is_published')->default(true),
                    Textarea::make('short_description')->rows(3)->columnSpanFull(),
                    TagsInput::make('features')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Detail Page')
                ->schema([
                    TextInput::make('headline'),
                    TextInput::make('subheadline'),
                    Textarea::make('description')->rows(6)->columnSpanFull(),
                    Repeater::make('benefits')
                        ->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('text')->rows(2)->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('process_steps')
                        ->schema([
                            TextInput::make('step')->required(),
                            Textarea::make('desc')->rows(2)->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('faq_items')
                        ->schema([
                            TextInput::make('q')->required(),
                            Textarea::make('a')->rows(3)->required(),
                        ])
                        ->columnSpanFull(),
                ]),
            Section::make('SEO')
                ->schema([
                    TextInput::make('seo_title'),
                    Textarea::make('seo_description')->rows(3),
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
