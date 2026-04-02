<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Models\Page;
use App\Support\AutoSlug;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Page')
                    ->collapsible()
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
                        Toggle::make('is_published')->default(true),
                        TextInput::make('meta_title'),
                        Textarea::make('meta_description')->rows(3),
                    ])
                    ->columns(1),
                Section::make('Sections')
                    ->collapsible()
                    ->schema([
                        Repeater::make('sections')
                            ->relationship()
                            ->defaultItems(0)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(function (array $state): string {
                                $type = trim((string) ($state['type'] ?? ''));
                                $label = trim((string) ($state['content']['title'] ?? ''));
                                $key = trim((string) ($state['key'] ?? ''));

                                $typeLabel = match ($type) {
                                    'faq' => 'FAQ',
                                    'hero' => 'Hero',
                                    'content' => 'Content',
                                    'stats' => 'Stats',
                                    'cards' => 'Cards',
                                    'cta' => 'CTA',
                                    default => 'Section',
                                };

                                if ($label !== '') {
                                    return "{$typeLabel}: {$label}";
                                }

                                return $key !== ''
                                    ? "{$typeLabel}: {$key}"
                                    : $typeLabel;
                            })
                            ->schema([
                                TextInput::make('key')->required(),
                                Select::make('type')
                                    ->options([
                                        'hero' => 'Hero',
                                        'content' => 'Content',
                                        'stats' => 'Stats',
                                        'cards' => 'Cards',
                                        'faq' => 'FAQ',
                                        'cta' => 'CTA',
                                    ])
                                    ->required(),
                                TextInput::make('sort_order')->numeric()->default(0),
                                Toggle::make('is_enabled')->default(true),
                                TextInput::make('content.badge'),
                                TextInput::make('content.eyebrow'),
                                TextInput::make('content.title'),
                                TextInput::make('content.highlight'),
                                TextInput::make('content.afterHighlight'),
                                TextInput::make('content.buttonText'),
                                TextInput::make('content.buttonUrl'),
                                TextInput::make('content.primaryButtonText'),
                                TextInput::make('content.primaryButtonUrl'),
                                TextInput::make('content.secondaryButtonText'),
                                TextInput::make('content.secondaryButtonUrl'),
                                Tabs::make('Description')
                                    ->columnSpanFull()
                                    ->tabs([
                                        Tab::make('View')
                                            ->schema([
                                                RichEditor::make('content.description')
                                                    ->label('Description')
                                                    ->live(onBlur: true)
                                                    ->afterStateHydrated(function (Get $get, Set $set, mixed $state): void {
                                                        $description = static::resolveRichDescriptionState(
                                                            $state,
                                                            static::replacesItemsWithDescription($get)
                                                                ? $get('content.items')
                                                                : null,
                                                        );

                                                        if ($description !== static::normalizeRichDescriptionStateValue($state)) {
                                                            $set('content.description', $description);
                                                        }

                                                        $set('content.description_html', static::formatHtmlForEditor($description));

                                                        if (static::replacesItemsWithDescription($get)) {
                                                            $set('content.items', []);
                                                        }
                                                    })
                                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                                        $description = static::normalizeRichDescriptionStateValue($state);

                                                        $set('content.description', $description);
                                                        $set('content.description_html', static::formatHtmlForEditor($description));

                                                        if (static::replacesItemsWithDescription($get)) {
                                                            $set('content.items', []);
                                                        }
                                                    })
                                                    ->helperText('Edit this section visually, then switch to Code when you need direct HTML access.')
                                                    ->columnSpanFull(),
                                            ]),
                                        Tab::make('Code')
                                            ->schema([
                                                CodeEditor::make('content.description_html')
                                                    ->label('Description HTML')
                                                    ->language(Language::Html)
                                                    ->wrap()
                                                    ->dehydrated(false)
                                                    ->live(onBlur: true)
                                                    ->afterStateHydrated(function (CodeEditor $component, Get $get, Set $set, mixed $state): void {
                                                        $description = static::resolveRichDescriptionState(
                                                            $get('content.description'),
                                                            static::replacesItemsWithDescription($get)
                                                                ? $get('content.items')
                                                                : null,
                                                        );

                                                        if ($description !== static::normalizeRichDescriptionStateValue($get('content.description'))) {
                                                            $set('content.description', $description);
                                                        }

                                                        if (static::replacesItemsWithDescription($get)) {
                                                            $set('content.items', []);
                                                        }

                                                        $component->state(static::formatHtmlForEditor(
                                                            static::normalizeRichDescriptionStateValue($state) ?: $description,
                                                        ));
                                                    })
                                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                                        $description = static::normalizeRichDescriptionStateValue($state);

                                                        $set('content.description', $description);
                                                        $set('content.description_html', static::formatHtmlForEditor($description));

                                                        if (static::replacesItemsWithDescription($get)) {
                                                            $set('content.items', []);
                                                        }
                                                    })
                                                    ->helperText('Edit the stored HTML directly.')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                                TagsInput::make('content.rotatingWords'),
                                Repeater::make('content.items')
                                    ->label(function (Get $get): string {
                                        return match ($get('type')) {
                                            'faq' => 'FAQ Items',
                                            'cards' => 'Card Items',
                                            'stats' => 'Stat Items',
                                            default => 'Items',
                                        };
                                    })
                                    ->helperText(function (Get $get): ?string {
                                        return match ($get('type')) {
                                            'faq' => 'Add the questions and answers for this FAQ section.',
                                            'cards' => 'Add the card title and description only.',
                                            'stats' => 'Add the stat label, end value, and suffix only.',
                                            default => null,
                                        };
                                    })
                                    ->defaultItems(0)
                                    ->visible(fn (Get $get): bool => ! static::replacesItemsWithDescription($get))
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(function (array $state): string {
                                        foreach (['q', 'title', 'label', 'text', 'value'] as $key) {
                                            $value = trim((string) ($state[$key] ?? ''));

                                            if ($value !== '') {
                                                return $value;
                                            }
                                        }

                                        return 'Item';
                                    })
                                    ->schema([
                                        TextInput::make('title')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'stats'], true)),
                                        TextInput::make('highlight')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('tagline')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('label')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards'], true)),
                                        TextInput::make('value')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('text')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('href')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('iconKey')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('buttonText')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('buttonUrl')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        TextInput::make('suffix')
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards'], true)),
                                        TextInput::make('end')
                                            ->numeric()
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards'], true)),
                                        TextInput::make('q')
                                            ->label('Q')
                                            ->visible(fn (Get $get): bool => $get('../../../type') === 'faq'),
                                        Textarea::make('a')
                                            ->label('A')
                                            ->rows(3)
                                            ->visible(fn (Get $get): bool => $get('../../../type') === 'faq'),
                                        Textarea::make('description')
                                            ->rows(2)
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'cards', 'stats'], true)),
                                        Textarea::make('desc')
                                            ->rows(2)
                                            ->visible(fn (Get $get): bool => ! in_array($get('../../../type'), ['faq', 'stats'], true)),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),
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
                EditAction::make()
                    ->modalWidth(Width::SevenExtraLarge),
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
            'index' => ManagePages::route('/'),
        ];
    }

    protected static function replacesItemsWithDescription(Get $get): bool
    {
        $slug = trim((string) ($get('../../slug') ?? ''));

        if ($slug !== '') {
            return $slug === 'about' && $get('type') === 'content' && $get('key') === 'intro';
        }

        return $get('type') === 'content' && $get('key') === 'intro';
    }

    protected static function resolveRichDescriptionState(mixed $description, mixed $items): string
    {
        if (! is_array($items) && is_array($description)) {
            $items = $description['items'] ?? $items;
        }

        $description = static::normalizeRichDescriptionStateValue($description);

        if ($description !== '') {
            return $description;
        }

        if (! is_array($items)) {
            return '';
        }

        $paragraphs = collect($items)
            ->map(fn (mixed $item): string => trim((string) data_get($item, 'text')))
            ->filter()
            ->values()
            ->all();

        if ($paragraphs === []) {
            return '';
        }

        return static::paragraphsToHtml($paragraphs);
    }

    protected static function normalizeRichDescriptionStateValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (! is_array($value)) {
            return '';
        }

        foreach (['description', 'description_html', 'html', 'text'] as $key) {
            $candidate = static::normalizeRichDescriptionStateValue($value[$key] ?? null);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param  list<string>  $paragraphs
     */
    protected static function paragraphsToHtml(array $paragraphs): string
    {
        return collect($paragraphs)
            ->map(function (string $paragraph): string {
                $escaped = htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return '<p>' . nl2br($escaped, false) . '</p>';
            })
            ->implode(PHP_EOL);
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

        $wrapperAttribute = 'data-page-description-editor-root';
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
