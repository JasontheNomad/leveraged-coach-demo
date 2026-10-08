<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return redirect()->guest('/login');
        }

        if (auth()->user()->role !== 'admin') {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
