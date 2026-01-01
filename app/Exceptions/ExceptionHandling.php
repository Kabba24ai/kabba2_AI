<?php

namespace App\Exceptions;

use Illuminate\Http\Request;

// Exceptions
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionHandling
{
    public static function render(\Throwable $exception, Request $request)
    {
        if ($exception instanceof ThrottleRequestsException) {
            flash('Too Many Requests. Please try after 1 minute.')->error();
            return back();
        }

        // Handling token mismatch exception.
        if (
            $exception instanceof TokenMismatchException
            || (
                $exception instanceof HttpException
                && $exception->getStatusCode() == 419
            )
        ) {
            return self::handleTokenMismatchException($request);
        }


        //   // 404 → MASTER 301 redirect to homepage
        // if ($exception instanceof NotFoundHttpException) {

        //     // Prevent infinite redirect loop
        //     if ($request->path() === '/') {
        //         return null;
        //     }

        //     return redirect('/', 301);
        // }

        // // Let Laravel handle everything else
        // return null;

    }

    /**
     * The actual response for TokenMismatchException (419)
     */
    private static function handleTokenMismatchException(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session has expired. Please refresh the page and try again.'
            ], 419);
        }

        return redirect()
            ->back()
            ->withInput($request->except('_token'))
            ->with('error', 'Your session has expired. Please refresh the page and try again.');
    }
}
