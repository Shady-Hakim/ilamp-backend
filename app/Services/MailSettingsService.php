<?php

namespace App\Services;

use App\Models\MailSetting;
use Illuminate\Mail\MailManager;

class MailSettingsService
{
    public function apply(): bool
    {
        $settings = MailSetting::query()->first();

        return $settings
            ? $this->applyFromData([], $settings)
            : false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function applyFromData(array $data, ?MailSetting $record = null): bool
    {
        $settings = $this->resolveSettings($data, $record);

        if (
            blank($settings['host'])
            || blank($settings['port'])
            || blank($settings['username'])
            || blank($settings['password'])
            || blank($settings['from_email'])
        ) {
            return false;
        }

        config([
            'mail.default' => $settings['mailer'] ?: 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.encryption' => $settings['encryption'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.mailers.smtp.timeout' => 10,
            'mail.from.address' => $settings['from_email'],
            'mail.from.name' => $settings['from_name'] ?: config('app.name'),
        ]);

        app(MailManager::class)->forgetMailers();

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     mailer: string,
     *     host: mixed,
     *     port: mixed,
     *     encryption: mixed,
     *     username: mixed,
     *     password: mixed,
     *     from_email: mixed,
     *     from_name: mixed,
     *     reply_to: mixed
     * }
     */
    protected function resolveSettings(array $data, ?MailSetting $record = null): array
    {
        return [
            'mailer' => $data['mailer'] ?? $record?->mailer ?? 'smtp',
            'host' => $data['host'] ?? $record?->host,
            'port' => $data['port'] ?? $record?->port,
            'encryption' => array_key_exists('encryption', $data) ? $data['encryption'] : $record?->encryption,
            'username' => $data['username'] ?? $record?->username,
            'password' => filled($data['password'] ?? null) ? $data['password'] : $record?->password,
            'from_email' => $data['from_email'] ?? $record?->from_email,
            'from_name' => $data['from_name'] ?? $record?->from_name,
            'reply_to' => $data['reply_to'] ?? $record?->reply_to,
        ];
    }
}
