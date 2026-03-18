<?php

namespace App\Http\Controllers\Admin\Configurations\ProductSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\ProductSettings\SaveRequest;

// Models
use App\Models\Configurations\Setting;
use App\Models\ProductManagement\Product;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            $setting = Setting::where('setting_name', $key)->whereIn('setting_type', ['Product Settings', 'Allocated Hours Settings'])->first();
            if ($setting) {
                // Special case for sales tax
                if ($setting->setting_name === 'sales_tax') {
                    // Convert percentage input to decimal
                    $value = floatval($value) / 100;
                }

                if ($setting->setting_name === 'special_taxes') {
                    // Convert percentage input to decimal
                    $value = floatval($value) / 100;
                }

                if ($setting->setting_name === 'prepaid_fuel_rates' || $setting->setting_name === 'prepaid_cleaning_rates') {
                    // Remove empty values from fuel array
                    $jsonData = array_filter($validated[$key] ?? [], function ($item) {
                        return !empty($item['description']) && isset($item['rate']) && $item['rate'] !== '';
                    });
                    $value = json_encode(array_values($jsonData));

                    if ($setting->setting_name === 'prepaid_fuel_rates') {
                        foreach ($jsonData as $index => $item) {
                            $jsonData[$index]['rate'] = floatval($item['rate']);
                            Product::where('prepaid_fuel_rate_setting', $item['description'])->update(['rental_prepaid_fuel' => $item['rate']]);
                        }
                    }

                    if ($setting->setting_name === 'prepaid_cleaning_rates') {
                        foreach ($jsonData as $index => $item) {
                            Product::where('prepaid_cleaning_rate_setting', $item['description'])->update(['rental_prepaid_cleaning' => $item['rate']]);
                        }
                    }
                }

                $setting->setting_value = $value ?? null;
                $setting->save();
            }
        }

        /**
         * NEW: Push track-insurance fees to products based on size.
         * Only update a column if the corresponding field was present in the request.
         */
        $sizeMap = [
            'Small' => 'small',
            'Medium' => 'medium',
            'Large' => 'large',
            'X-Large' => 'x_large',
            '2X-Large' => '2x_large',
            'Commercial' => 'commercial',
        ];

        $periodToProductColumn = [
            'daily' => 'rental_track_insurance_daily',
            'weekend' => 'rental_track_insurance_weekend',
            'weekly' => 'rental_track_insurance_weekly',
            'monthly' => 'rental_track_insurance_monthly',
        ];

        foreach ($sizeMap as $dbSize => $prefix) {
            // Build update payload only for fields actually provided
            $update = [];

            foreach ($periodToProductColumn as $period => $column) {
                $key = "{$prefix}_{$period}_track_insurance_fee";

                if ($request->has($key)) {
                    // validated() guarantees numeric|min:0|nullable, so cast if not null
                    $val = $request->input($key);
                    $update[$column] = $val === null || $val === '' ? null : floatval($val);
                }
            }

            if (!empty($update)) {
                Product::query()
                    ->where('track_insurance_size_setting', $dbSize) // exact values in your DB
                    ->update($update);
            }
        }

        foreach ($sizeMap as $dbSize => $prefix) {
            $update = [];

            // Standard delivery fee (only if present in request)
            $stdKey = "{$prefix}_standard_delivery_fee";
            if ($request->has($stdKey)) {
                $val = $request->input($stdKey);
                $update['standard_delivery_fee'] = $val === null || $val === '' ? null : floatval($val);
            }

            // Extended delivery fee (only if present in request)
            $extKey = "{$prefix}_extended_delivery_fee";
            if ($request->has($extKey)) {
                $val = $request->input($extKey);
                $update['extended_delivery_fee'] = $val === null || $val === '' ? null : floatval($val);
            }

            if (!empty($update)) {
                Product::query()
                    ->where('truck_fee_size_setting', $dbSize) // match exact text saved in products
                    ->update($update);
            }
        }

        flash()->success(__('Product Settings updated successfully.'));
        return redirect()->back();
    }
}
