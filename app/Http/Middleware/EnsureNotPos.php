<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotPos
{
    /**
     * Handle an incoming request.
     *
     * Blocks users with the 'POS' role from administrative, settings, reports,
     * and general management features, redirecting them safely to the POS terminal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'POS') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthorized. POS accounts are restricted to the Point of Sale terminal.'
                ], 403);
            }

            return redirect()->route('pos.index');
        }

        return $next($request);
    }
}
