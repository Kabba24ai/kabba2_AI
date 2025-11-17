<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Footer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;

use App\Models\Configurations\Setting;



class IndexController extends Controller
{
    public function __invoke(Request $request)
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

        return view('admin.website_management.footer.index', compact('settings'));
    }
}
