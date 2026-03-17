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

        // Prevent clickjacking via iframe embedding.
        $response->headers->set(
            'X-Frame-Options',
            env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN')
        );

        // Prevent MIME-type sniffing.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable legacy XSS filter for older browsers.
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Enforce HTTPS for one year. Only sent over secure connections.
        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains; preload')
            );
        }

        // Control referrer information sent to third-party origins.
        $response->headers->set(
            'Referrer-Policy',
            env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin')
        );

        // Disable browser features not used by this application.
        $response->headers->set(
            'Permissions-Policy',
            env(
                'SECURITY_PERMISSIONS_POLICY',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
            )
        );

        // Use SECURITY_CSP in .env to override the policy entirely,
        // or remove it to let buildCsp() handle dev/production automatically.
        $response->headers->set(
            'Content-Security-Policy',
            env('SECURITY_CSP') ?: $this->buildCsp()
        );

        return $response;
    }

    /**
     * Build a CSP policy tailored to the current environment.
     *
     * Local:      Relaxed to support Vite HMR (localhost:5173).
     * Production: Strict — no Vite origins, no unsafe-eval.
     *
     * Set VITE_PORT in .env if using a non-default Vite port.
     */
    protected function buildCsp(): string
    {
        $isLocal = app()->environment('local');

        $port = env('VITE_PORT', '5173');
        $vA   = "http://localhost:{$port}";
        $vB   = "http://127.0.0.1:{$port}";
        $wsA  = "ws://localhost:{$port}";
        $wsB  = "ws://127.0.0.1:{$port}";

        $directives = [
            // Local: allow Vite HMR origins and unsafe directives for hot-reload.
            // Production: static bundles served from 'self' only.
            $isLocal
                ? "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$vA} {$vB} https://cdn.jsdelivr.net https://cdnjs.cloudflare.com"
                : "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",

            // unsafe-inline required for inline <style> blocks and Alpine.js.
            // Local: also allow Vite origins for HMR style injection.
            $isLocal
                ? "style-src 'self' 'unsafe-inline' {$vA} {$vB} https://cdnjs.cloudflare.com https://fonts.googleapis.com"
                : "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",

            // Local: allow Vite WebSocket and HTTP for HMR.
            // Production: same-origin only.
            $isLocal
                ? "connect-src 'self' {$vA} {$vB} {$wsA} {$wsB}"
                : "connect-src 'self'",

            "img-src 'self' data:",
            "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",

            // Production only: upgrade any http:// sub-resources to https://.
            ...($isLocal ? [] : ['upgrade-insecure-requests']),
        ];

        return implode('; ', $directives);
    }
}