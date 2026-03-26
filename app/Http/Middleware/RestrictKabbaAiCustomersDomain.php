<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictKabbaAiCustomersDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedHosts = [
            'admin.rentnking.com',
            'admin.kabba.local',
            'admin.kabba.ai',
        ];

        $currentHost = strtolower((string) $request->getHost());

        if (!in_array($currentHost, $allowedHosts, true)) {
            abort(403, 'This page is not available on this domain.');
        }

        return $next($request);
    }
}
