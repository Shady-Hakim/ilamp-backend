<?php

namespace App\Filament\Resources\MailSettings;

use App\Filament\Resources\MailSettings\Pages\ManageMailSettings;
use App\Models\MailSetting;
use App\Services\MailSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailSettingResource extends Resource
{
    protected static ?string $model = MailSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function canCreate(): bool
    {
        return MailSetting::query()->count() === 0;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('SMTP Configuration')
                ->schema([
                    Select::make('mailer')
                        ->options([
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'log' => 'Log',
                        ])
                        ->default('smtp')
                        ->required(),
                    TextInput::make('host'),
                    TextInput::make('port')
                        ->numeric()
                        ->helperText('On local development, SMTP port 25 is often blocked. Prefer 465 (SSL) or 587 (TLS) if your mail provider supports them.'),
                    TextInput::make('username'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    Select::make('encryption')
                        ->options([
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                            null => 'None',
                        ]),
                ])
                ->description('The Test SMTP button checks the values currently entered in this popup. Save the record after a successful test so contact and reservation emails use the same settings.')
                ->columns(2),
            Section::make('Message Defaults')
                ->schema([
                    TextInput::make('from_name'),
                    TextInput::make('from_email')->email(),
                    TextInput::make('reply_to')->email(),
                    TextInput::make('notify_contact_to')->email(),
                    TextInput::make('notify_consultation_to')->email(),
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
                TextColumn::make('mailer'),
                TextColumn::make('host'),
                TextColumn::make('from_email'),
                TextColumn::make('updated_at')->since(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->extraModalFooterActions(fn (EditAction $action): array => [
                        Action::make('testSmtp')
                            ->label('Test SMTP')
                            ->icon(Heroicon::Envelope)
                            ->color('gray')
                            ->modalHeading('Send Test Email')
                            ->modalDescription('This uses the SMTP values currently entered in this popup, including unsaved edits. Save the Mail Settings record after a successful test to apply them to the whole app.')
                            ->schema([
                                TextInput::make('recipient')
                                    ->label('Test recipient')
                                    ->email()
                                    ->required(),
                            ])
                            ->fillForm(function (array $mountedActions, ?MailSetting $record): array {
                                $parentData = $mountedActions[0]->getRawData();

                                return [
                                    'recipient' => $parentData['from_email']
                                        ?? $parentData['reply_to']
                                        ?? $parentData['notify_contact_to']
                                        ?? $record?->from_email
                                        ?? $record?->reply_to
                                        ?? $record?->notify_contact_to,
                                ];
                            })
                            ->overlayParentActions()
                            ->action(function (
                                array $data,
                                array $mountedActions,
                                ?MailSetting $record,
                                MailSettingsService $mailSettingsService,
                            ): void {
                                $parentData = $mountedActions[0]->getRawData();

                                if (! $mailSettingsService->applyFromData($parentData, $record)) {
                                    Notification::make()
                                        ->warning()
                                        ->title('SMTP test could not start.')
                                        ->body('Please complete host, port, username, password, and from email first.')
                                        ->send();

                                    return;
                                }

                                try {
                                    Mail::raw(
                                        "This is a test email from the iLamp Mail Settings panel.\n\nIf you received this message, the current SMTP configuration is working correctly.",
                                        function ($message) use ($data, $parentData, $record): void {
                                            $message
                                                ->to($data['recipient'])
                                                ->subject('iLamp SMTP Configuration Test');

                                            $replyTo = $parentData['reply_to'] ?? $record?->reply_to;

                                            if (filled($replyTo)) {
                                                $message->replyTo($replyTo);
                                            }
                                        },
                                    );

                                    Notification::make()
                                        ->success()
                                        ->title('Test email sent.')
                                        ->body("A test email was sent to {$data['recipient']}. Save the Mail Settings record now if you want contact and reservation emails to use these same SMTP values.")
                                        ->send();
                                } catch (Throwable $exception) {
                                    Notification::make()
                                        ->danger()
                                        ->title('SMTP test failed.')
                                        ->body(self::formatSmtpTestFailureMessage($exception, $parentData))
                                        ->send();
                                }
                            }),
                    ]),
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
            'index' => ManageMailSettings::route('/'),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected static function formatSmtpTestFailureMessage(Throwable $exception, array $settings): string
    {
        $message = $exception->getMessage();
        $host = trim((string) ($settings['host'] ?? ''));
        $port = trim((string) ($settings['port'] ?? ''));
        $normalizedMessage = strtolower($message);

        if (str_contains($normalizedMessage, 'timed out')) {
            $hint = "The SMTP host {$host}:{$port} could not be reached from this machine.";

            if ($port === '25') {
                $hint .= ' Port 25 is commonly blocked on local networks and hosting providers. Try port 465 with SSL or 587 with TLS if your mail server supports them.';
            }

            return "{$message} {$hint}";
        }

        return $message;
    }
}
