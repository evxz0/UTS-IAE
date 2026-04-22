<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        if (count($roles) > 0 && !in_array(auth()->user()->role, $roles)) {
            // Redirect based on role if unauthorized
            if (auth()->user()->role === 'kasir') {
                return redirect()->route('pos.index');
            }
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
