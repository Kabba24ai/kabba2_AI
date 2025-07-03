<?php

namespace App\Http\Middleware\Front;

use Closure;
use Illuminate\Http\Request;

// Helpers
use App\Helpers\CommonFrontDataHelper;

class CommonDataMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        CommonFrontDataHelper::setCommonFrontData();

        return $next($request);
    }
}
