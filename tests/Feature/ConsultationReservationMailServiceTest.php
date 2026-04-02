<?php

namespace Tests\Feature;

use App\Mail\ConsultationReservationClientMail;
use App\Models\ConsultationEmailSetting;
use App\Models\ConsultationReservation;
use App\Models\MailSetting;
use App\Services\ConsultationReservationMailService;
use App\Services\MailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConsultationReservationMailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_saved_client_invitation_template_for_a_reservation(): void
    {
        $this->mock(MailSettingsService::class, function ($mock): void {
            $mock->shouldReceive('apply')->andReturnTrue();
        });

        MailSetting::query()->create([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'mailer',
            'password' => 'secret',
            'encryption' => 'tls',
            'from_name' => 'iLamp Team',
            'from_email' => 'hello@ilamp.test',
            'reply_to' => 'support@ilamp.test',
        ]);

        ConsultationEmailSetting::query()->firstOrFail()->update([
            'client_email_body' => '<p>Dear {{client_name}},</p><p>Your consultation is booked for {{meeting_window}}.</p><p>Meeting link: <a href="{{meeting_link}}">{{meeting_link}}</a></p><p>Reply to {{reply_to_email}} if you need help.</p>',
        ]);

        $reservation = ConsultationReservation::query()->create([
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '123456789',
            'status' => 'pending',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($reservation->email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->withArgs(function (ConsultationReservationClientMail $mail) use ($reservation): bool {
                return $mail->subjectLine === 'iLamp agency - your consultation meeting invitation'
                    && str_contains($mail->htmlBody, 'Dear Jane Doe,')
                    && str_contains($mail->htmlBody, 'Your consultation is booked for')
                    && str_contains($mail->htmlBody, 'https://meet.example.com/jane-doe')
                    && str_contains($mail->htmlBody, 'support@ilamp.test')
                    && str_contains($mail->textBody, 'https://meet.example.com/jane-doe')
                    && str_contains($mail->textBody, 'Jane Doe')
                    && $mail->reservation->is($reservation);
            });

        $error = app(ConsultationReservationMailService::class)->sendClientMessage(
            $reservation,
            'https://meet.example.com/jane-doe',
        );

        $this->assertNull($error);
    }

    public function test_it_sends_a_status_update_email_for_a_reservation(): void
    {
        $this->mock(MailSettingsService::class, function ($mock): void {
            $mock->shouldReceive('apply')->andReturnTrue();
        });

        MailSetting::query()->create([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'mailer',
            'password' => 'secret',
            'encryption' => 'tls',
            'from_name' => 'iLamp Team',
            'from_email' => 'hello@ilamp.test',
            'reply_to' => 'support@ilamp.test',
        ]);

        ConsultationEmailSetting::query()->firstOrFail()->update([
            'cancelled_email_body' => '<p>Hello {{client_name}},</p><p>Status: <strong>{{reservation_status}}</strong>.</p><p>Window: {{meeting_window}}</p>',
        ]);

        $reservation = ConsultationReservation::query()->create([
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '11:30',
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'status' => 'cancelled',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($reservation->email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->withArgs(function (ConsultationReservationClientMail $mail) use ($reservation): bool {
                return $mail->subjectLine === 'Your iLamp consultation reservation is now Cancelled'
                    && str_contains($mail->htmlBody, 'Hello John Smith,')
                    && str_contains($mail->htmlBody, 'Cancelled')
                    && str_contains($mail->textBody, 'Window:')
                    && $mail->reservation->is($reservation);
            });

        $error = app(ConsultationReservationMailService::class)->sendStatusUpdate($reservation);

        $this->assertNull($error);
    }
}
