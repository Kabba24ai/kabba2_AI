<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Models\Configurations\UserNotificationSetting;

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

        $newOrderRows = UserNotificationSetting::where('type', 'order')->get()->toArray();
        $emergencyRows = UserNotificationSetting::where('type', 'emergency')->get()->toArray();

        return view('admin.configurations.index', compact('settings','newOrderRows','emergencyRows'));
    }
}
