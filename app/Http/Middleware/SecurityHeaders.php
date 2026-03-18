<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'X-Frame-Options',
            env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN')
        );

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains; preload')
            );
        }

        $response->headers->set(
            'Referrer-Policy',
            env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin')
        );

        $response->headers->set(
            'Permissions-Policy',
            env(
                'SECURITY_PERMISSIONS_POLICY',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
            )
        );

        $response->headers->set(
            'Content-Security-Policy',
            env('SECURITY_CSP') ?: $this->buildCsp()
        );

        return $response;
    }

    protected function buildCsp(): string
    {
        $isLocal = app()->environment('local');

        $port = env('VITE_PORT', '5173');
        $vA   = "http://localhost:{$port}";
        $vB   = "http://127.0.0.1:{$port}";
        $wsA  = "ws://localhost:{$port}";
        $wsB  = "ws://127.0.0.1:{$port}";

        $directives = [
            $isLocal
                ? "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$vA} {$vB} https://cdn.jsdelivr.net https://cdnjs.cloudflare.com"
                : "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",

            $isLocal
                ? "style-src 'self' 'unsafe-inline' {$vA} {$vB} https://cdnjs.cloudflare.com https://fonts.googleapis.com"
                : "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",

            $isLocal
                ? "connect-src 'self' {$vA} {$vB} {$wsA} {$wsB}"
                : "connect-src 'self'",

            "img-src 'self' data:",
            "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",

            ...($isLocal ? [] : ['upgrade-insecure-requests']),
        ];

        return implode('; ', $directives);
    }
}