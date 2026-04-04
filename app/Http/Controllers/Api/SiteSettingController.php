<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteSettingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = SiteSetting::query()->first();
        $whatsappNumber = SiteSetting::normalizeWhatsappNumber($settings?->whatsapp_url);

        return response()->json([
            'siteName' => $settings?->site_name ?? 'iLamp Agency',
            'siteTagline' => $settings?->site_tagline,
            'footerDescription' => $settings?->footer_description,
            'contactEmail' => $settings?->contact_email,
            'contactPhone' => $settings?->contact_phone,
            'contactAddress' => $settings?->contact_address,
            'whatsappNumber' => $whatsappNumber,
            'whatsappUrl' => SiteSetting::buildWhatsappUrl($whatsappNumber),
            'responseTimeText' => $settings?->response_time_text,
            'socialLinks' => SiteSetting::normalizeSocialLinks($settings?->social_links),
            'recaptchaSiteKey' => filled($settings?->recaptcha_site_key) ? $settings->recaptcha_site_key : null,
        ]);
    }
}
