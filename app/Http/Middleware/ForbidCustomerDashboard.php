<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForbidCustomerDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->employee_status === 'customer') {
            // Redirect ke halaman lain, misal home
            return redirect()->route('home.cart');
        }
        return $next($request);
    }
}