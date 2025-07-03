<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;

// Models
use App\Models\Configurations\Setting;

class IndexController extends Controller
{
    public function __invoke()
    {

        // Load the settings from the database
        $settings = Setting::whereNotIn('setting_type', ['Email Settings'])->orderBy('sort_order','asc')->get()->groupBy('setting_type')->sortKeys(); // This will sort keys alphabetically (A-Z)

        // Return the view with the settings data
        return view('admin.configurations.index', compact('settings'));
    }
}
