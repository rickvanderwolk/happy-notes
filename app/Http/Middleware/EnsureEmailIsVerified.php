<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_email_verification') && (! $request->user() || ! $request->user()->hasVerifiedEmail())) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
