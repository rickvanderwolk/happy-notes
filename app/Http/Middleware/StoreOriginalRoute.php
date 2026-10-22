<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * We use current route name to highlight menu items for example.
 * When livewire is updated the route name will become "livewire.update".
 * As a workaround we use the session to store the "original" route name for now.
 */
final class StoreOriginalRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = Route::current();

        if ($route) {
            $routeName = $route->getName();

            if ($routeName) {
                session(['original_route_name' => $routeName]);
            }
        }

        return $next($request);
    }
}
