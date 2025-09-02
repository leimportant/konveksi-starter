<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowIframe
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Ganti dengan domain parent iframe kamu
        $allowedDomain = "https://aninkafashion.com";

        // Atur Content-Security-Policy agar iframe bisa load dari parentdomain.com
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self' $allowedDomain"
        );

        return $response;
    }
}
