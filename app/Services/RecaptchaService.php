<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    public function isEnabled(): bool
    {
        $settings = SiteSetting::query()->first();

        return filled($settings?->recaptcha_site_key) && filled($settings?->recaptcha_secret_key);
    }

    /**
     * Verify a reCAPTCHA v3 token submitted from the frontend.
     * Returns true if the token is valid (or if reCAPTCHA is not configured).
     * v3 returns a score (0.0–1.0); we require >= 0.5 to accept the submission.
     */
    public function verify(?string $token): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $secret = SiteSetting::query()->value('recaptcha_secret_key');

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secret,
                'response' => $token,
            ]);

            $body = $response->json();

            if (! ($body['success'] ?? false)) {
                return false;
            }

            // v3 score: 1.0 = very likely human, 0.0 = very likely bot
            $score = (float) ($body['score'] ?? 0.0);

            return $score >= 0.5;
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed.', ['error' => $e->getMessage()]);

            // Fail open — don't block submissions when Google is unreachable
            return true;
        }
    }
}
