<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationEmailSetting extends Model
{
    protected $guarded = [];

    public const CLIENT_EMAIL_BODY = 'client_email_body';

    public const PENDING_EMAIL_BODY = 'pending_email_body';

    public const CONFIRMED_EMAIL_BODY = 'confirmed_email_body';

    public const CANCELLED_EMAIL_BODY = 'cancelled_email_body';

    public const COMPLETED_EMAIL_BODY = 'completed_email_body';

    public const NO_SHOW_EMAIL_BODY = 'no_show_email_body';

    /**
     * @return array<int, string>
     */
    public static function templateFields(): array
    {
        return [
            static::CLIENT_EMAIL_BODY,
            static::PENDING_EMAIL_BODY,
            static::CONFIRMED_EMAIL_BODY,
            static::CANCELLED_EMAIL_BODY,
            static::COMPLETED_EMAIL_BODY,
            static::NO_SHOW_EMAIL_BODY,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultBodies(): array
    {
        return [
            static::CLIENT_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>Thank you for choosing {{site_name}}. We are delighted to welcome you and confirm your consultation with our team.</p>
<p>You are warmly invited to join your scheduled meeting on {{reservation_date}}, from {{reservation_time_range}}.</p>
<p>Meeting link: <a href="{{meeting_link}}">{{meeting_link}}</a></p>
<p>If you have any questions before the meeting, please reply to this email at {{reply_to_email}}, and we will be pleased to assist you.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
            static::PENDING_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>Thank you for choosing {{site_name}}.</p>
<p>Your consultation reservation for {{meeting_window}} is currently marked as <strong>{{reservation_status}}</strong>. Our team will review the booking and follow up if any adjustment is needed.</p>
<p>If you have any questions, please reply to this email at {{reply_to_email}} and we will be glad to assist you.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
            static::CONFIRMED_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>We are pleased to confirm your consultation with {{site_name}}.</p>
<p>Your reservation is scheduled for {{meeting_window}}, and the current status is <strong>{{reservation_status}}</strong>.</p>
<p>If you need any assistance before the meeting, please reply to this email at {{reply_to_email}}.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
            static::CANCELLED_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>We would like to inform you that your consultation reservation with {{site_name}} for {{meeting_window}} is now marked as <strong>{{reservation_status}}</strong>.</p>
<p>If you would like to arrange another time, please reply to this email at {{reply_to_email}} and our team will be happy to help.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
            static::COMPLETED_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>Thank you for meeting with {{site_name}}.</p>
<p>Your consultation scheduled for {{meeting_window}} has been marked as <strong>{{reservation_status}}</strong>.</p>
<p>If you would like to continue the conversation, please reply to this email at {{reply_to_email}} and our team will be pleased to assist you.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
            static::NO_SHOW_EMAIL_BODY => <<<'HTML'
<p>Dear {{client_name}},</p>
<p>We noticed that the consultation reserved for {{meeting_window}} was marked as <strong>{{reservation_status}}</strong>.</p>
<p>If you would like to reschedule, please reply to this email at {{reply_to_email}} and our team will gladly help you arrange a new time.</p>
<p>Best regards,<br>{{site_name}} team</p>
HTML,
        ];
    }

    public static function defaultBody(string $field): string
    {
        return static::defaultBodies()[$field] ?? '';
    }

    public static function statusBodyField(string $status): string
    {
        return match ($status) {
            'pending' => static::PENDING_EMAIL_BODY,
            'confirmed' => static::CONFIRMED_EMAIL_BODY,
            'cancelled' => static::CANCELLED_EMAIL_BODY,
            'completed' => static::COMPLETED_EMAIL_BODY,
            'no_show' => static::NO_SHOW_EMAIL_BODY,
            default => static::PENDING_EMAIL_BODY,
        };
    }

    public function clientBody(): string
    {
        return $this->resolvedBody(static::CLIENT_EMAIL_BODY);
    }

    public function statusBody(string $status): string
    {
        return $this->resolvedBody(static::statusBodyField($status));
    }

    protected function resolvedBody(string $field): string
    {
        $body = trim((string) $this->getAttribute($field));

        if ($body !== '') {
            return $body;
        }

        return static::defaultBody($field);
    }
}
