<?php

namespace App\Filament\Resources\ConsultationEmailSettings;

use App\Filament\Resources\ConsultationEmailSettings\Pages\ManageConsultationEmailSettings;
use App\Models\ConsultationEmailSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationEmailSettingResource extends Resource
{
    protected static ?string $model = ConsultationEmailSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::EnvelopeOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email Bodies';

    public static function canCreate(): bool
    {
        return ConsultationEmailSetting::query()->count() === 0;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->default('Consultation Emails')
                        ->maxLength(255),
                ]),
            Tabs::make('Email Bodies')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Consultation Emails')
                        ->schema([
                            Section::make('Email Client')
                                ->description(static::placeholderHelpText(includeMeetingLink: true))
                                ->schema([
                                    static::richHtmlEditor(ConsultationEmailSetting::CLIENT_EMAIL_BODY, 'Email body'),
                                ]),
                            Section::make('Pending Status')
                                ->description(static::placeholderHelpText())
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    static::richHtmlEditor(ConsultationEmailSetting::PENDING_EMAIL_BODY, 'Email body'),
                                ]),
                            Section::make('Cancelled Status')
                                ->description(static::placeholderHelpText())
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    static::richHtmlEditor(ConsultationEmailSetting::CANCELLED_EMAIL_BODY, 'Email body'),
                                ]),
                            Section::make('Completed Status')
                                ->description(static::placeholderHelpText())
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    static::richHtmlEditor(ConsultationEmailSetting::COMPLETED_EMAIL_BODY, 'Email body'),
                                ]),
                            Section::make('No Show Status')
                                ->description(static::placeholderHelpText())
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    static::richHtmlEditor(ConsultationEmailSetting::NO_SHOW_EMAIL_BODY, 'Email body'),
                                ]),
                        ]),
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
                TextColumn::make('name')->searchable(),
                TextColumn::make('updated_at')->since()->label('Updated'),
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
            'index' => ManageConsultationEmailSettings::route('/'),
        ];
    }

    protected static function richHtmlEditor(string $field, string $label): Tabs
    {
        $codeField = "{$field}_html";
        $defaultBody = ConsultationEmailSetting::defaultBody($field);

        return Tabs::make("{$field}_tabs")
            ->columnSpanFull()
            ->tabs([
                Tab::make('View')
                    ->schema([
                        RichEditor::make($field)
                            ->label($label)
                            ->live(onBlur: true)
                            ->helperText('Edit the email visually, then switch to Code when you need direct HTML access.')
                            ->afterStateHydrated(function (Set $set, ?string $state) use ($field, $codeField, $defaultBody): void {
                                $resolvedState = filled($state) ? $state : $defaultBody;

                                if (blank($state)) {
                                    $set($field, $resolvedState);
                                }

                                $set($codeField, static::formatHtmlForEditor($resolvedState));
                            })
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($codeField): void {
                                $set($codeField, static::formatHtmlForEditor($state));
                            })
                            ->columnSpanFull(),
                    ]),
                Tab::make('Code')
                    ->schema([
                        CodeEditor::make($codeField)
                            ->label('Email body HTML')
                            ->language(Language::Html)
                            ->wrap()
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->helperText('Edit the stored HTML directly.')
                            ->afterStateHydrated(function (CodeEditor $component, Get $get, ?string $state) use ($field, $defaultBody): void {
                                $component->state(static::formatHtmlForEditor($state ?: $get($field) ?: $defaultBody));
                            })
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($field, $codeField): void {
                                $set($field, $state);
                                $set($codeField, static::formatHtmlForEditor($state));
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function placeholderHelpText(bool $includeMeetingLink = false): string
    {
        $placeholders = [
            '{{client_name}}',
            '{{client_email}}',
            '{{client_phone}}',
            '{{client_company}}',
            '{{reservation_date}}',
            '{{reservation_time_range}}',
            '{{meeting_window}}',
            '{{reservation_status}}',
            '{{reply_to_email}}',
            '{{site_name}}',
        ];

        if ($includeMeetingLink) {
            $placeholders[] = '{{meeting_link}}';
        }

        return 'Available placeholders: '.implode(', ', $placeholders).'.';
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

        $wrapperAttribute = 'data-consultation-email-editor-root';
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
