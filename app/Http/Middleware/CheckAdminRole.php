<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated and has the admin role
        if (Auth::check() && Auth::user()->role === 2) {
            return $next($request); // Allow the request to proceed
        }

        // If the user is not an admin, return a 403 Forbidden response
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Admins only.',
            'status_code' => 403
        ], 403);
    }
}
