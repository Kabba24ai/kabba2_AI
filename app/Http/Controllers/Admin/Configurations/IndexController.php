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
        $settings = Setting::whereNotIn('setting_type', ['Email Settings'])->get()->groupBy('setting_type');

        // Return the view with the settings data
        return view('admin.configurations.index', compact('settings'));
    }
}
