<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Vite and Livewire put this nonce on the scripts they render, so only those run.
        Vite::useCspNonce();

        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => "geolocation=(), microphone=(), camera=()",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        ];

        if (app()->environment('production')) {
            $headers['Cross-Origin-Opener-Policy'] = 'same-origin';
            $headers['Cross-Origin-Resource-Policy'] = 'same-origin';
            $headers['X-Permitted-Cross-Domain-Policies'] = 'none';
        }

        // Report-only for now: the browser logs what the policy would block without
        // blocking it, so it can be checked against real use before it is enforced. The
        // Vite dev server serves its scripts from another origin, so it is left out there.
        if (!Vite::isRunningHot()) {
            $headers['Content-Security-Policy-Report-Only'] = $this->contentSecurityPolicy();
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Alpine, which Livewire ships, evaluates its expressions at runtime, hence
     * 'unsafe-eval'. Inline styles are allowed for the style attributes in the views
     * and the ones Editor.js sets.
     */
    private function contentSecurityPolicy(): string
    {
        $nonce = Vite::cspNonce();

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' 'report-sample'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
