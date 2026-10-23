<?php

namespace App\Providers;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12));

        // Disable rate limiting only where the tests run, so a misnamed environment on a
        // server keeps it on instead of silently switching it off.
        if (App::environment(['local', 'testing'])) {
            RateLimiter::for('global', fn () => Limit::none());
            RateLimiter::for('api', fn () => Limit::none());
            RateLimiter::for('web', fn () => Limit::none());
            app()->bind(ThrottleRequests::class, fn () => new class () {
                public function handle(Request $request, Closure $next): mixed
                {
                    return $next($request);
                }
            });
        }
    }
}
