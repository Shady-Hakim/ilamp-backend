<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultationReservation;
use App\Models\MailSetting;
use App\Models\SiteSetting;
use App\Services\ConsultationAvailabilityService;
use App\Services\MailSettingsService;
use App\Services\RecaptchaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ConsultationController extends Controller
{
    public function availability(Request $request, ConsultationAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        return response()->json([
            'month' => $validated['month'],
            'dates' => collect($availabilityService->availabilityForMonth($validated['month']))
                ->map(fn (array $date): array => [
                    'date' => $date['date'],
                    'slotCount' => $date['slot_count'],
                ])
                ->values(),
        ]);
    }

    public function slots(Request $request, ConsultationAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json(
            $availabilityService->slotsForDate(Carbon::parse($validated['date']))
        );
    }

    public function store(
        Request $request,
        ConsultationAvailabilityService $availabilityService,
        MailSettingsService $mailSettingsService,
        RecaptchaService $recaptcha,
    ): JsonResponse {
        $data = $request->validate([
            'date'            => ['required', 'date_format:Y-m-d'],
            'start'           => ['required', 'date_format:H:i'],
            'end'             => ['required', 'date_format:H:i'],
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'phone'           => ['required', 'string', 'max:255'],
            'company'         => ['nullable', 'string', 'max:255'],
            'message'         => ['nullable', 'string', 'max:5000'],
            'recaptcha_token' => ['nullable', 'string'],
        ]);

        if (! $recaptcha->verify($request->input('recaptcha_token'))) {
            return response()->json(['message' => 'reCAPTCHA verification failed. Please try again.'], 422);
        }

        $availableSlots = collect($availabilityService->slotsForDate(Carbon::parse($data['date'])));
        $matchingSlot = $availableSlots->first(
            fn (array $slot): bool => $slot['start'] === $data['start'] && $slot['end'] === $data['end']
        );

        if (! $matchingSlot) {
            return response()->json([
                'message' => 'This slot is no longer available.',
            ], 409);
        }

        try {
            $reservation = ConsultationReservation::query()->create([
                'date' => $data['date'],
                'start_time' => $data['start'],
                'end_time' => $data['end'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'company' => $data['company'],
                'message' => $data['message'],
                'status' => 'pending',
                'source' => 'website',
            ]);
        } catch (QueryException) {
            return response()->json([
                'message' => 'This slot was just booked by someone else.',
            ], 409);
        }

        $this->sendNotification($reservation->toArray(), $mailSettingsService);

        return response()->json([
            'message' => 'Consultation reserved successfully.',
            'reservation' => [
                'id' => $reservation->id,
                'date' => $reservation->date,
                'start' => $reservation->start_time,
                'end' => $reservation->end_time,
                'status' => $reservation->status,
            ],
        ], 201);
    }

    protected function sendNotification(array $reservation, MailSettingsService $mailSettingsService): void
    {
        if (! $mailSettingsService->apply()) {
            return;
        }

        $mailSettings = MailSetting::query()->first();
        $recipient = $mailSettings?->notify_consultation_to
            ?: SiteSetting::query()->value('contact_email');

        if (! $recipient) {
            return;
        }

        $body = implode("\n\n", [
            'New consultation reservation received.',
            'Name: '.$reservation['name'],
            'Email: '.$reservation['email'],
            'Phone: '.$reservation['phone'],
            'Company: '.($reservation['company'] ?: 'Not provided'),
            'Date: '.$reservation['date'],
            'Time: '.$reservation['start_time'].' - '.$reservation['end_time'],
            'Message:',
            $reservation['message'] ?: 'No message provided.',
        ]);

        try {
            Mail::raw($body, function ($message) use ($recipient, $mailSettings, $reservation): void {
                $message->to($recipient)
                    ->subject('iLamp Consultation: '.$reservation['date'].' '.$reservation['start_time'])
                    ->replyTo($mailSettings?->reply_to ?: $reservation['email']);
            });
        } catch (Throwable $exception) {
            Log::warning('Failed to send consultation notification email.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
