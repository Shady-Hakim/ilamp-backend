<?php

namespace App\Filament\Resources\PortfolioProjects;

use App\Filament\Resources\PortfolioProjects\Pages\ManagePortfolioProjects;
use App\Livewire\MediaCollectionPicker;
use App\Models\PortfolioProject;
use App\Support\AutoSlug;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfolioProjectResource extends Resource
{
    protected static ?string $model = PortfolioProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
            Section::make('Overview')
                ->columnSpanFull()
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
                    TextInput::make('year')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(2100),
                    TextInput::make('project_url')
                        ->label('Project link')
                        ->url()
                        ->maxLength(2048)
                        ->placeholder('https://example.com')
                        ->helperText('Optional. Used for the Preview Project button on the frontend.'),
                    DateTimePicker::make('published_at')
                        ->default(fn (): \Illuminate\Support\Carbon => now()),
                    Livewire::make(MediaCollectionPicker::class, [
                        'collection' => 'featured_image',
                        'label' => 'Project image',
                        'multiple' => false,
                    ])->columnSpanFull(),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_published')->default(true),
                    Textarea::make('short_description')->rows(3)->maxLength(1000)->columnSpanFull(),
                    Textarea::make('brief')->rows(5)->columnSpanFull(),
                    Select::make('categories')
                        ->relationship(name: 'categories', titleAttribute: 'name')
                        ->multiple()
                        ->preload()
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Section::make('Client and Delivery')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('client')->maxLength(255),
                    Livewire::make(MediaCollectionPicker::class, [
                        'collection' => 'client_logo',
                        'label' => 'Client logo',
                        'multiple' => false,
                    ])->columnSpanFull(),
                    Textarea::make('client_brief')->rows(3)->columnSpanFull(),
                    TagsInput::make('tech_stack')->columnSpanFull(),
                    TagsInput::make('results')->columnSpanFull(),
                    Livewire::make(MediaCollectionPicker::class, [
                        'collection' => 'gallery',
                        'label' => 'Gallery images',
                        'multiple' => true,
                    ])->columnSpanFull(),
                    Textarea::make('challenge')->rows(4)->columnSpanFull(),
                    Textarea::make('solution')->rows(4)->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug')->copyable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('year'),
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
            'index' => ManagePortfolioProjects::route('/'),
        ];
    }
}
