<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleShortName
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$shortNames)
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        // Check by short_name
        $hasRole = $user->roles()
            ->whereIn('short_name', $shortNames)
            ->exists();

        if (!$hasRole) {
            abort(403, 'User does not have the right role');
        }

        return $next($request);
    }
}
