<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;

class IndexController extends Controller
{
    public function __invoke()
    {
        // Group by string key (no enum)

        $settings = Setting::whereNotIn('setting_type', ['Email Settings'])
            ->whereNotIn('setting_name', ['prepaid_cleaning_rates', 'prepaid_fuel_rates'])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('setting_type'); // keys are strings

        $settings = $settings->sortKeys();

        // dd($settings);

        return view('admin.configurations.index', compact('settings'));
    }
}
