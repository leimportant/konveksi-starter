<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForbidCustomerDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->employee_status === 'customer') {
            // Redirect ke halaman lain, misal home
            return redirect()->route('home.cart');
        }
        return $next($request);
    }
}