<?php

namespace App\Filament\Resources\SiteSettings;

use App\Filament\Resources\SiteSettings\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function canCreate(): bool
    {
        return SiteSetting::query()->count() === 0;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Brand')
                ->schema([
                    TextInput::make('site_name')->required()->maxLength(255),
                    TextInput::make('site_tagline')->maxLength(255),
                    Textarea::make('footer_description')->rows(4)->columnSpanFull(),
                ])
                ->columns(1),
            Section::make('Contact')
                ->schema([
                    TextInput::make('contact_email')->email(),
                    TextInput::make('contact_phone'),
                    TextInput::make('contact_address'),
                    TextInput::make('whatsapp_url')
                        ->label('WhatsApp Number')
                        ->tel()
                        ->placeholder('+201555164031')
                        ->formatStateUsing(fn (?string $state): ?string => SiteSetting::normalizeWhatsappNumber($state))
                        ->dehydrateStateUsing(fn (?string $state): ?string => SiteSetting::normalizeWhatsappNumber($state))
                        ->helperText('Enter the WhatsApp number with country code. Example: +201555164031'),
                    TextInput::make('response_time_text')->columnSpanFull(),
                ])
                ->columns(1),
            Section::make('Social Links')
                ->schema([
                    Repeater::make('social_links')
                        ->schema([
                            Select::make('icon')
                                ->options(SiteSetting::socialIconOptions())
                                ->required()
                                ->searchable(),
                            TextInput::make('url')->url()->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
            Section::make('Google reCAPTCHA')
                ->schema([
                    TextInput::make('recaptcha_site_key')
                        ->label('Site Key (public)')
                        ->maxLength(255)
                        ->placeholder('6Le...')
                        ->helperText('Paste the Site Key from your Google reCAPTCHA admin console.'),
                    TextInput::make('recaptcha_secret_key')
                        ->label('Secret Key (private)')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->placeholder('6Le...')
                        ->helperText('Paste the Secret Key. It is stored securely and never exposed to the frontend.'),
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
            ->columns([
                TextColumn::make('site_name')->searchable(),
                TextColumn::make('contact_email'),
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
            'index' => ManageSiteSettings::route('/'),
        ];
    }
}
