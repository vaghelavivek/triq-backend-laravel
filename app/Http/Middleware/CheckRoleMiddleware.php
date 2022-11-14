<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next,$role)
    {
        if (auth()->check() && $role=='super-admin' && auth()->user()->role_id != 1) {
            $response = [
                'status' => false,
                'message' => 'Unauthenticated',
            ];
            return response()->json($response,401);
        }
        return $next($request);
    }
}
