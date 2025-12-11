<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
// use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Log;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = parent::share($request);

        // Hanya tambahkan Ziggy / semua routes jika APP_DEBUG = true
        if (config('app.debug')) {
            $shared['allRoutes'] = fn() => collect(\Illuminate\Support\Facades\Route::getRoutes())->map(fn($route) => [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
                'action' => $route->getActionName(),
                'middleware' => $route->middleware(),
            ]);
        }

        // Jika ada debug trigger via URL
        // if (config('app.debug') && $request->has('debug_inertia')) {
        //     abort(response()->json($shared, 500)); // dark page JSON
        // }

        return $shared;
    }


}
