<?php

use App\Models\ConsultationEmailSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $oldDefault = <<<'HTML'
<p>Dear {{client_name}},</p>
<p>Thank you for choosing {{site_name}}. We are delighted to welcome you and confirm your consultation with our team.</p>
<p>You are warmly invited to join your scheduled meeting on {{reservation_date}}, from {{reservation_time_range}}.</p>
<p>If you have any questions before the meeting, please reply to this email at {{reply_to_email}}, and we will be pleased to assist you.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML;

        ConsultationEmailSetting::query()
            ->where(function ($query) use ($oldDefault): void {
                $query
                    ->whereNull('client_email_body')
                    ->orWhere('client_email_body', '')
                    ->orWhere('client_email_body', $oldDefault);
            })
            ->update([
                'client_email_body' => ConsultationEmailSetting::defaultBody(ConsultationEmailSetting::CLIENT_EMAIL_BODY),
            ]);
    }

    public function down(): void
    {
        //
    }
};
