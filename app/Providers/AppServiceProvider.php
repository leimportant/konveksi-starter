<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
      Inertia::share([
            'auth' => [
                'user' => fn () => Auth::user()?->load('roles'),
            ],
        ]);
    }

}
