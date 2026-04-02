<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Services\MailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_unsaved_mail_settings_and_reuses_existing_password(): void
    {
        $record = MailSetting::query()->create([
            'mailer' => 'smtp',
            'host' => 'smtp.old.test',
            'port' => 587,
            'username' => 'old-user',
            'password' => 'old-secret',
            'encryption' => 'tls',
            'from_name' => 'Old Sender',
            'from_email' => 'old@example.com',
            'reply_to' => 'reply-old@example.com',
        ]);

        $applied = app(MailSettingsService::class)->applyFromData([
            'mailer' => 'smtp',
            'host' => 'smtp.new.test',
            'port' => 465,
            'username' => 'new-user',
            'password' => '',
            'encryption' => 'ssl',
            'from_name' => 'New Sender',
            'from_email' => 'new@example.com',
            'reply_to' => 'reply-new@example.com',
        ], $record);

        $this->assertTrue($applied);
        $this->assertSame('smtp.new.test', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('new-user', config('mail.mailers.smtp.username'));
        $this->assertSame('old-secret', config('mail.mailers.smtp.password'));
        $this->assertSame(10, config('mail.mailers.smtp.timeout'));
        $this->assertSame('new@example.com', config('mail.from.address'));
        $this->assertSame('New Sender', config('mail.from.name'));
    }

    public function test_it_returns_false_when_required_mail_settings_are_missing(): void
    {
        $applied = app(MailSettingsService::class)->applyFromData([
            'mailer' => 'smtp',
            'host' => null,
            'port' => 587,
            'username' => 'mailer',
            'password' => 'secret',
            'from_email' => 'sender@example.com',
        ]);

        $this->assertFalse($applied);
    }
}
