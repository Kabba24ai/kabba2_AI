<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Cache;

class SetTimezoneFromIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        try {

            // Cache key for this IP
            $cacheKey = "ip_timezone:{$ip}";

            // Try to get timezone from cache
            $timezone = Cache::remember($cacheKey, now()->addHours(12), function () use ($ip) {
                $location = Location::get($ip);
                return $location && $location->timezone ? $location->timezone : null;
            });
            // If no timezone found, use the default application timezone
            if (!$timezone) {
                $timezone = config('app.timezone');
            }else {
                //\Log::info('Detected timezone for IP', ['ip' => $ip, 'timezone' => $timezone]);
                // If a timezone is found, set it in the config
                Config::set('app.timezone', $timezone);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to detect timezone', ['ip' => $ip,'error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
