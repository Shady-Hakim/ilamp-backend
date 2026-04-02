<?php

namespace App\Mail;

use App\Models\ConsultationReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsultationReservationClientMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ConsultationReservation $reservation,
        public string $subjectLine,
        public string $htmlBody,
        public string $textBody,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.consultation.client-update')
            ->text('emails.consultation.client-update-text')
            ->with([
                'htmlBody' => $this->htmlBody,
                'textBody' => $this->textBody,
            ]);
    }
}
