<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Models\Configurations\UserNotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __invoke()
    {
        $settings = Setting::whereNotIn('setting_type', ['Email Settings'])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('setting_type'); // keys are strings

        session()->forget('master_verified');
        $settings = $settings->sortKeys();
        $settings = $settings->map(function ($group) {
            return $group->keyBy('setting_name');
        });

        $newOrderRows = UserNotificationSetting::where('type', 'order')->orderBy('name')->get()->toArray();
        $emergencyRows = UserNotificationSetting::where('type', 'emergency')->orderBy('name')->get()->toArray();



        /*
        |--------------------------------------------------------------------------
        | Fetch Current Application Code
        |--------------------------------------------------------------------------
        */

        $currentApplicationCode = null;

        try {

            $currentAdminUrl = rtrim(url('/'), '/') . '/';

            // Log::info('Fetching Application Code Started', [

            //     'url' => config(
            //         'services.kabba_client_api.get_url'
            //     ),

            //     'current_admin_url' => $currentAdminUrl,

            // ]);

            $response = Http::withoutVerifying()
                ->timeout(20)
                ->get(
                    config('services.kabba_client_api.get_url'),
                    [
                        'current_admin_url' => $currentAdminUrl,
                    ]
                );

            // Log::info('Application Code API Response', [

            //     'status' => $response->status(),

            //     'response' => $response->json(),

            // ]);

            if ($response->successful()) {

                $currentApplicationCode = $response->json(
                    'application_code'
                );

                // Log::info('Application Code Loaded', [

                //     'application_code' => $currentApplicationCode,

                // ]);

            } else {

                // Log::warning('Application Code API Failed', [

                //     'status' => $response->status(),

                //     'response' => $response->body(),

                // ]);
            }

        } catch (\Exception $e) {

            Log::error('Unable To Fetch Application Code', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */


        return view('admin.configurations.index', compact('settings','newOrderRows','emergencyRows','currentApplicationCode'));
    }
}
