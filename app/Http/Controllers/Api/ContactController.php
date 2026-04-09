<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\MailSetting;
use App\Models\SiteSetting;
use App\Services\MailSettingsService;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request, MailSettingsService $mailSettingsService, RecaptchaService $recaptcha): JsonResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'subject'           => ['nullable', 'string', 'max:255'],
            'message'           => ['required', 'string', 'max:5000'],
            'recaptcha_token'   => ['nullable', 'string'],
        ]);

        if (! $recaptcha->verify($request->input('recaptcha_token'))) {
            return response()->json(['message' => 'reCAPTCHA verification failed. Please try again.'], 422);
        }

        $message = ContactMessage::query()->create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ]);

        $this->sendNotification($data, $mailSettingsService);

        return response()->json([
            'message' => 'Your message has been received.',
            'id' => $message->id,
        ], 201);
    }

    protected function sendNotification(array $data, MailSettingsService $mailSettingsService): void
    {
        if (! $mailSettingsService->apply()) {
            return;
        }

        $mailSettings = MailSetting::query()->first();
        $recipient = $mailSettings?->notify_contact_to ?: SiteSetting::query()->value('contact_email');

        if (! $recipient) {
            return;
        }

        $body = implode("\n\n", [
            'New contact message received.',
            'Name: '.$data['name'],
            'Email: '.$data['email'],
            'Subject: '.($data['subject'] ?: 'No subject'),
            'Message:',
            $data['message'],
        ]);

        try {
            Mail::raw($body, function ($message) use ($recipient, $mailSettings, $data): void {
                $message->to($recipient)
                    ->subject('iLamp Contact: '.($data['subject'] ?: 'New inquiry'))
                    ->replyTo($mailSettings?->reply_to ?: $data['email']);
            });
        } catch (Throwable $exception) {
            Log::warning('Failed to send contact notification email.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
