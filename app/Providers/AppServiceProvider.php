<?php

namespace App\Providers;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Disable rate limiting if not production (for e2e tests)
        if (!App::environment('production')) {
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
