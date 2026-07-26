<?php

namespace App\Http\Controllers\Admin\Configurations\GoogleMaps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\GoogleMaps\GoogleMapsRequest;
use App\Models\Configurations\Setting;

/**
 * Persists the global "Google Maps & Routing" integration settings. Mirrors the
 * Payment Integration convention: the save is gated behind the master-password
 * verification (session 'master_verified'), and the encrypted API key row is
 * written through the Setting model so its encryption mutator fires.
 */
class SaveController extends Controller
{
    private const GROUP = 'Google Maps Settings';

    public function __invoke(GoogleMapsRequest $request)
    {
        $validated = $request->validated();

        if (session('master_verified') !== true) {
            flash()->error(__('You havent verified your master code.'));
            return redirect()->back();
        }

        foreach ($validated as $key => $value) {
            // Never overwrite the stored secret with the masked placeholder or a
            // blank — the field is only submitted with a real value after the
            // operator unlocks and replaces it.
            if ($key === 'google_maps_api_key') {
                if ($value === null || trim((string) $value) === '' || str_contains((string) $value, '*')) {
                    continue;
                }
            }

            if ($setting = Setting::where('setting_name', $key)->where('setting_type', self::GROUP)->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Google Maps & Routing settings updated successfully.'));
        return redirect()->back();
    }
}
