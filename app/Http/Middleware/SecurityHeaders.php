<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        // Prevent this page from being embedded in an iframe on other origins (clickjacking).
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent browsers from MIME-sniffing a response away from the declared Content-Type.
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Control how much referrer info is included with requests.
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Legacy XSS auditor header — still respected by some older browsers.
        $headers->set('X-XSS-Protection', '1; mode=block');

        // Permissions policy: disable powerful browser features the app doesn't use.
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS: only send over HTTPS to avoid breaking plain-HTTP development.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
