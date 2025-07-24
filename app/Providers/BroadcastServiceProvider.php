<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
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
        // Configure the routes for channel authorization with proper middleware
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Register the channel authorization routes
        require base_path('routes/channels.php');
        
        // Log when the broadcast service provider is booted
        \Illuminate\Support\Facades\Log::info('BroadcastServiceProvider booted', [
            'driver' => config('broadcasting.default'),
            'connection' => config('broadcasting.connections.' . config('broadcasting.default'))
        ]);
    }
}