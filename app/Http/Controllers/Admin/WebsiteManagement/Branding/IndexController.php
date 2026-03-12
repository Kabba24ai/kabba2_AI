<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Branding;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $settings = Setting::whereIn('setting_type', ['Profile Settings'])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('setting_type'); // keys are strings

        $settings = $settings->sortKeys();
        $settings = $settings->map(function ($group) {
            return $group->keyBy('setting_name');
        });


        return view('admin.website_management.branding.index', compact('settings'));
    }
}
