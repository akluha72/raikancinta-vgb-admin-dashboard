<?php

namespace App\Providers;

use App\Models\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared {event} route binding for BOTH the owner dashboard (which
        // links to events by id) and the public APIs (which address them by
        // slug). Numeric value → id; otherwise → slug. A miss is always a 404,
        // and event_id is always resolved server-side — never client-injected.
        Route::bind('event', function (string $value) {
            return ctype_digit($value)
                ? Event::findOrFail($value)
                : Event::where('slug', $value)->firstOrFail();
        });
    }
}
