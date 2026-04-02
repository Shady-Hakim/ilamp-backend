<?php

namespace App\Services;

use App\Mail\ConsultationReservationClientMail;
use App\Models\ConsultationEmailSetting;
use App\Models\ConsultationReservation;
use App\Models\MailSetting;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ConsultationReservationMailService
{
    public function __construct(
        protected MailSettingsService $mailSettingsService,
    ) {}

    public function sendClientMessage(ConsultationReservation $reservation, string $meetingLink): ?string
    {
        $meetingLink = trim($meetingLink);

        if ($meetingLink === '') {
            return 'Meeting link is required.';
        }

        return $this->send(
            reservation: $reservation,
            subject: 'iLamp agency - your consultation meeting invitation',
            htmlBody: $this->renderTemplate(
                $this->consultationEmailSetting()->clientBody(),
                $reservation,
                $meetingLink,
            ),
        );
    }

    public function sendStatusUpdate(ConsultationReservation $reservation): ?string
    {
        $statusLabel = $this->statusLabel($reservation->status);

        return $this->send(
            reservation: $reservation,
            subject: "Your iLamp consultation reservation is now {$statusLabel}",
            htmlBody: $this->renderTemplate(
                $this->consultationEmailSetting()->statusBody($reservation->status),
                $reservation,
                null,
            ),
        );
    }

    protected function send(
        ConsultationReservation $reservation,
        string $subject,
        string $htmlBody,
    ): ?string {
        if (blank($reservation->email)) {
            return 'This reservation does not have a client email address.';
        }

        $htmlBody = trim($htmlBody);

        if ($htmlBody === '') {
            return 'Email body is required.';
        }

        if (! $this->mailSettingsService->apply()) {
            return 'SMTP settings are not configured.';
        }

        $mailSettings = MailSetting::query()->first();
        $replyTo = $mailSettings?->reply_to
            ?: SiteSetting::query()->value('contact_email')
            ?: $mailSettings?->from_email;

        try {
            $mailable = new ConsultationReservationClientMail(
                reservation: $reservation,
                subjectLine: $subject,
                htmlBody: $htmlBody,
                textBody: $this->plainTextFromHtml($htmlBody),
            );

            if ($replyTo) {
                $mailable->replyTo($replyTo);
            }

            Mail::to($reservation->email)->send($mailable);
        } catch (Throwable $exception) {
            Log::warning('Failed to send consultation client email.', [
                'reservation_id' => $reservation->id,
                'email' => $reservation->email,
                'error' => $exception->getMessage(),
            ]);

            return 'The email could not be sent. Check the SMTP settings and try again.';
        }

        return null;
    }

    protected function consultationEmailSetting(): ConsultationEmailSetting
    {
        return ConsultationEmailSetting::query()->first()
            ?? new ConsultationEmailSetting(ConsultationEmailSetting::defaultBodies());
    }

    protected function renderTemplate(string $template, ConsultationReservation $reservation, ?string $meetingLink): string
    {
        return strtr($template, $this->templateReplacements($reservation, $meetingLink));
    }

    /**
     * @return array<string, string>
     */
    protected function templateReplacements(ConsultationReservation $reservation, ?string $meetingLink): array
    {
        return [
            '{{client_name}}' => e((string) $reservation->name),
            '{{client_email}}' => e((string) $reservation->email),
            '{{client_phone}}' => e((string) $reservation->phone),
            '{{client_company}}' => e((string) ($reservation->company ?? '')),
            '{{reservation_date}}' => e($this->formatDate($reservation->date)),
            '{{reservation_time_range}}' => e($this->formatTimeRange($reservation->start_time, $reservation->end_time)),
            '{{meeting_window}}' => e($this->formatMeetingWindow($reservation)),
            '{{meeting_link}}' => e(trim((string) $meetingLink)),
            '{{reservation_status}}' => e($this->statusLabel($reservation->status)),
            '{{reply_to_email}}' => e($this->replyToEmail()),
            '{{site_name}}' => e($this->siteName()),
        ];
    }

    protected function plainTextFromHtml(string $html): string
    {
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $normalized = preg_replace('/<\s*\/p\s*>/i', "\n\n", $normalized) ?? $normalized;
        $normalized = preg_replace('/<\s*\/li\s*>/i', "\n", $normalized) ?? $normalized;
        $normalized = preg_replace('/<\s*li[^>]*>/i', '- ', $normalized) ?? $normalized;

        $text = strip_tags($normalized);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('F j, Y');
    }

    protected function formatTimeRange(string $startTime, string $endTime): string
    {
        return Carbon::parse($startTime)->format('g:i A').' - '.Carbon::parse($endTime)->format('g:i A');
    }

    protected function formatMeetingWindow(ConsultationReservation $reservation): string
    {
        return $this->formatDate($reservation->date).' from '.$this->formatTimeRange($reservation->start_time, $reservation->end_time);
    }

    protected function statusLabel(string $status): string
    {
        return Str::headline($status);
    }

    protected function replyToEmail(): string
    {
        $mailSettings = MailSetting::query()->first();

        return (string) (
            $mailSettings?->reply_to
            ?: SiteSetting::query()->value('contact_email')
            ?: $mailSettings?->from_email
            ?: config('mail.from.address')
            ?: ''
        );
    }

    protected function siteName(): string
    {
        return (string) (
            SiteSetting::query()->value('site_name')
            ?: config('mail.from.name')
            ?: config('app.name')
        );
    }
}
