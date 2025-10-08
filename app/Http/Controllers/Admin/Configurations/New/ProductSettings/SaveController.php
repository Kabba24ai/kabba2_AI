<?php

namespace App\Http\Controllers\Admin\Configurations\New\ProductSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\ProductSettings\SaveRequest;

// Models
use App\Models\Configurations\Setting;


class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            $setting = Setting::where('setting_name', $key)->where('setting_type', 'Product Settings')->first();
            if ($setting) {

                // Special case for sales tax
                if ($setting->setting_name === 'sales_tax') {
                    // Convert percentage input to decimal
                    $value = floatval($value) / 100;
                }

                if ($setting->setting_name === 'prepaid_fuel_rates' || $setting->setting_name === 'prepaid_cleaning_rates') {
                    // Remove empty values from fuel array
                    $jsonData = array_filter($validated[$key] ?? [], function($item) {
                        return !empty($item['description']) && isset($item['rate']) && $item['rate'] !== '';
                    });
                    $value = json_encode(array_values($jsonData));
                }

                $setting->setting_value = $value ?? null;
                $setting->save();
            }
        }
        flash()->success(__('Settings updated successfully.'));
        return redirect()->back();
    }
}
