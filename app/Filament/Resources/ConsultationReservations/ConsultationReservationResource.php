<?php

namespace App\Filament\Resources\ConsultationReservations;

use App\Filament\Resources\ConsultationReservations\Pages\ManageConsultationReservations;
use App\Models\ConsultationReservation;
use App\Services\ConsultationReservationMailService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationReservationResource extends Resource
{
    protected static ?string $model = ConsultationReservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDateRange;

    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';

    /**
     * @return array<string, string>
     */
    protected static function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'no_show' => 'No Show',
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->required(),
            TimePicker::make('start_time')->seconds(false)->required(),
            TimePicker::make('end_time')->seconds(false)->required(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255),
            TextInput::make('phone')->required()->maxLength(50),
            TextInput::make('company')->maxLength(255),
            Select::make('status')
                ->options(self::statusOptions())
                ->helperText('Saving a changed status will email the client automatically, except Confirmed. Use Email Client to send the meeting invitation and confirm the reservation.')
                ->required(),
            TextInput::make('source')->maxLength(100),
            TextInput::make('meeting_link')
                ->label('Meeting link')
                ->url()
                ->placeholder('https://meet.google.com/...')
                ->helperText('Saved here and pre-filled in the Email Client popup. Use {{meeting_link}} in the Email Client template.')
                ->columnSpanFull(),
            Textarea::make('message')
                ->label('Client message')
                ->rows(5)
                ->maxLength(5000)
                ->columnSpanFull(),
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
                TextColumn::make('date')->date(),
                TextColumn::make('start_time')->time('g:i A'),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email'),
                SelectColumn::make('status')
                    ->options(self::statusOptions())
                    ->selectablePlaceholder(false)
                    ->updateStateUsing(function (
                        SelectColumn $column,
                        string $state,
                        ConsultationReservationMailService $mailService,
                    ): string {
                        /** @var ConsultationReservation $record */
                        $record = $column->getRecord();
                        $originalStatus = $record->status;

                        if ($state === $originalStatus) {
                            return $state;
                        }

                        $record->update([
                            'status' => $state,
                        ]);

                        self::sendStatusUpdateNotification($record, $mailService);

                        return $state;
                    }),
                TextColumn::make('created_at')->since(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('emailClient')
                    ->label('Email Client')
                    ->icon(Heroicon::Envelope)
                    ->color('gray')
                    ->modalHeading(fn (ConsultationReservation $record): string => "Email {$record->name}")
                    ->modalDescription('Add the meeting link to send the saved Email Client template.')
                    ->modalSubmitActionLabel('Send Email')
                    ->schema([
                        TextInput::make('meeting_link')
                            ->label('Meeting link')
                            ->url()
                            ->placeholder('https://meet.google.com/...')
                            ->helperText('This value is available in Settings > Email Bodies > Email Client as {{meeting_link}}. It will also be saved to the reservation.')
                            ->default(fn (ConsultationReservation $record): ?string => $record->meeting_link)
                            ->required(),
                    ])
                    ->action(function (
                        array $data,
                        ConsultationReservation $record,
                        ConsultationReservationMailService $mailService,
                    ): void {
                        $wasConfirmed = $record->status === 'confirmed';
                        $meetingLink = $data['meeting_link'];

                        // Persist the (possibly updated) meeting link
                        if ($record->meeting_link !== $meetingLink) {
                            $record->update(['meeting_link' => $meetingLink]);
                        }

                        $error = $mailService->sendClientMessage($record, $meetingLink);

                        if ($error) {
                            Notification::make()
                                ->danger()
                                ->title('Invitation email was not sent.')
                                ->body($error)
                                ->send();

                            return;
                        }

                        if (! $wasConfirmed) {
                            $record->update([
                                'status' => 'confirmed',
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Invitation email sent.')
                            ->body(
                                $wasConfirmed
                                    ? "The saved Email Client template was sent to {$record->email}."
                                    : "The saved Email Client template was sent to {$record->email}, and the reservation status was changed to Confirmed."
                            )
                            ->send();
                    })
                    ->tooltip('Opens a popup for the meeting link, then sends the saved Email Client template.')
                    ->disabled(fn (ConsultationReservation $record): bool => blank($record->email)),
                EditAction::make()
                    ->using(function (
                        ConsultationReservation $record,
                        array $data,
                        ConsultationReservationMailService $mailService,
                    ): void {
                        $originalStatus = $record->status;

                        $record->update($data);

                        if (($data['status'] ?? null) === $originalStatus) {
                            return;
                        }

                        self::sendStatusUpdateNotification($record, $mailService);
                    }),
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
            'index' => ManageConsultationReservations::route('/'),
        ];
    }

    protected static function sendStatusUpdateNotification(
        ConsultationReservation $record,
        ConsultationReservationMailService $mailService,
    ): void {
        if ($record->status === 'confirmed') {
            Notification::make()
                ->success()
                ->title('Reservation saved.')
                ->body('Status changed to Confirmed. Use Email Client to send the meeting invitation.')
                ->send();

            return;
        }

        $error = $mailService->sendStatusUpdate($record);

        if ($error) {
            Notification::make()
                ->warning()
                ->title('Reservation saved, but the status email was not sent.')
                ->body($error)
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Reservation saved and the client was notified.')
            ->body("A status update email was sent to {$record->email}.")
            ->send();
    }
}
