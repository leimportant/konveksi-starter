<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Inertia\Inertia; 

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
        // Optional: Log jika ada request JSON (misalnya dari fetch/axios luar Inertia)
       if ($request->expectsJson() && !$request->header('X-Inertia')) {
            \Log::debug('[Inertia Share] JSON fallback response dipanggil', [
                'url' => $request->url(),
                'headers' => $request->headers->all(),
            ]);
        }


        // Optional: pisahkan quote agar tidak dikirim saat API/json
        $quote = null;
        if (!$request->expectsJson()) {
            [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
            $quote = ['message' => trim($message), 'author' => trim($author)];
        }

        return [
            ...parent::share($request),

            // Global config
            'appName' => config('app.name'),

            // Optional quote (hanya kirim saat request normal)
            'quote' => $quote,

            // Auth info
            'auth' => [
                'user' => $request->user(),
            ],

            // Ziggy route info (untuk Vue routing helper)
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            // Flash messages (rekomendasi)
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ];
    }

}
