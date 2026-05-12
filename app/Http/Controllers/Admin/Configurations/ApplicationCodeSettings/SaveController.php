<?php

namespace App\Http\Controllers\Admin\Configurations\ApplicationCodeSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\ApplicationCodeSettings\SaveRequest;

// Laravel
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Verify Master Code
        |--------------------------------------------------------------------------
        */

        if ($request->has('application_code')) {

            if (session('master_verified') !== true) {

                flash()->error(__('You havent verified your master code.'));

                return redirect()->back();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | API Request Log
        |--------------------------------------------------------------------------
        */

        Log::info('Application Code API Request Started', [
            'application_code' => strtoupper(
                $validated['application_code']
            ),
            'url' => config('services.kabba_client_api.url'),
        ]);

        try {

            // $response = Http::timeout(20)->post(
            //     config('services.kabba_client_api.url'),
            //     [
            //         'application_code' => strtoupper(
            //             $validated['application_code']
            //         ),
            //     ]
            // );


             $response = Http::withoutVerifying()
        ->timeout(20)
        ->post(
            config('services.kabba_client_api.url'),
            [
                'application_code' => strtoupper(
                    $validated['application_code']
                ),

                'current_admin_url' => url('/'),
            ]
        );


            /*
            |--------------------------------------------------------------------------
            | API Response Log
            |--------------------------------------------------------------------------
            */

            Log::info('Application Code API Response', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | API Failed
            |--------------------------------------------------------------------------
            */

            if (!$response->successful()) {

                flash()->error(
                    $response->json('message')
                    ?? 'Unable to save Application Code.'
                );

                return redirect()->back();
            }

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Exception Log
            |--------------------------------------------------------------------------
            */

            Log::error('Application Code API Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            flash()->error(
                __('Unable to connect to Client API.')
            );

            return redirect()->back();
        }

        flash()->success(__('Application Code updated successfully.'));

        return redirect()->back();
    }
}