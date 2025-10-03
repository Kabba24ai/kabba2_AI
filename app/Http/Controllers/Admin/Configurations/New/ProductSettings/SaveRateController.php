<?php

namespace App\Http\Controllers\Admin\Configurations\New\ProductSettings;

use App\Http\Controllers\Controller;

// Models
use App\Models\Configurations\Setting;
use Illuminate\Http\Request;

class SaveRateController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'fuel' => 'nullable|array',
            'fuel.*.description' => 'string|max:255',
            'fuel.*.rate' => 'numeric|min:0',
            'clean' => 'nullable|array',
            'clean.*.description' => 'string|max:255',
            'clean.*.rate' => 'nullable|numeric|min:0',
            'daily_hours' => 'nullable|numeric|min:0',
            'weekend_hours' => 'nullable|numeric|min:0',
            'weekly_hours' => 'nullable|numeric|min:0',
            'monthly_hours' => 'nullable|numeric|min:0',
        ]);


        Setting::where('setting_name', 'daily_hours')->update(['setting_value' => $validated['daily_hours'] ?? null]);
        Setting::where('setting_name', 'weekend_hours')->update(['setting_value' => $validated['weekend_hours'] ?? null]);
        Setting::where('setting_name', 'weekly_hours')->update(['setting_value' => $validated['weekly_hours'] ?? null]);
        Setting::where('setting_name', 'monthly_hours')->update(['setting_value' => $validated['monthly_hours'] ?? null]);

        // Remove empty values from fuel array
        $fuelData = array_filter($validated['fuel'] ?? [], function($item) {
            return !empty($item['description']) && isset($item['rate']) && $item['rate'] !== '';
        });

        $cleanData = array_filter($validated['clean'] ?? [], function($item) {
            return !empty($item['description']) && isset($item['rate']) && $item['rate'] !== '';
        });

        // Convert to JSON
        $fuelJson = json_encode(array_values($fuelData));
        $cleanJson = json_encode(array_values($cleanData));

        // Update settings in the database
        Setting::where('setting_name', 'prepaid_fuel_rates')->update(['setting_value' => $fuelJson]);
        Setting::where('setting_name', 'prepaid_cleaning_rates')->update(['setting_value' => $cleanJson]);

        flash()->success(__('Settings updated successfully.'));
        return redirect()->back();
    }
}
