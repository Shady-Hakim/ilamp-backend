<?php

namespace App\Filament\Resources\BlogPosts;

use App\Livewire\MediaCollectionPicker;
use App\Filament\Resources\BlogPosts\Pages\ManageBlogPosts;
use App\Models\BlogPost;
use App\Support\AutoSlug;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Newspaper;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->columnSpanFull()
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
                    TextInput::make('author_name')
                        ->default('iLamp Team')
                        ->placeholder('iLamp Team'),
                    Livewire::make(MediaCollectionPicker::class, [
                        'collection' => 'image',
                        'label' => 'Featured image',
                        'multiple' => false,
                    ])->columnSpanFull(),
                    DateTimePicker::make('published_at'),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_published')->default(true),
                    Select::make('categories')
                        ->relationship(name: 'categories', titleAttribute: 'name')
                        ->multiple()
                        ->preload()
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Optional. If left empty, it will be generated from the opening lines of the body when you save.'),
                    Tabs::make('Body')
                        ->columnSpanFull()
                        ->tabs([
                            Tab::make('View')
                                ->schema([
                                    RichEditor::make('body')
                                        ->label('Body')
                                        ->live(onBlur: true)
                                        ->afterStateHydrated(function (Set $set, ?string $state): void {
                                            $set('body_html', static::formatHtmlForEditor($state));
                                        })
                                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                                            $set('body_html', static::formatHtmlForEditor($state));
                                        })
                                        ->helperText('Edit the blog post visually, then switch to Code when you need direct HTML access.')
                                        ->columnSpanFull(),
                                ]),
                            Tab::make('Code')
                                ->schema([
                                    CodeEditor::make('body_html')
                                        ->label('Body HTML')
                                        ->language(Language::Html)
                                        ->wrap()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateHydrated(function (CodeEditor $component, Get $get, ?string $state): void {
                                            $component->state(static::formatHtmlForEditor($state ?? $get('body')));
                                        })
                                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                                            $set('body', $state);
                                            $set('body_html', static::formatHtmlForEditor($state));
                                        })
                                        ->helperText('Edit the stored HTML directly.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->columns(2),
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
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                IconColumn::make('is_featured')->boolean(),
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
            'index' => ManageBlogPosts::route('/'),
        ];
    }

    protected static function formatHtmlForEditor(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '' || ! preg_match('/<[^>]+>/', $html)) {
            return $html;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $document->preserveWhiteSpace = false;

        $wrapperAttribute = 'data-blog-editor-root';
        $wrappedHtml = sprintf('<div %s="1">%s</div>', $wrapperAttribute, $html);

        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?>' . $wrappedHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($document);
        $wrapper = $xpath->query(sprintf('//*[@%s="1"]', $wrapperAttribute))->item(0);

        if (! $wrapper instanceof \DOMElement) {
            return $html;
        }

        $formattedFragments = [];

        foreach (iterator_to_array($wrapper->childNodes) as $childNode) {
            $formatted = trim($document->saveHTML($childNode));

            if ($formatted !== '') {
                $formattedFragments[] = $formatted;
            }
        }

        return $formattedFragments !== []
            ? implode(PHP_EOL, $formattedFragments)
            : $html;
    }
}
