<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class SetTimezoneFromIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $location = geoip()->getLocation($request->ip());

            if (!empty($location['timezone'])) {
                Config::set('app.timezone', $location['timezone']);
            }
        } catch (\Exception $e) {
            // fallback to default timezone
        }
        return $next($request);
    }
}
