<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HighDemandAlert;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Services\Website\HighDemandAlertService;

class IndexController extends Controller
{
    public function __invoke(HighDemandAlertService $alert)
    {
        // Raw stored values (may be null) — the form shows what is actually
        // saved, while $config carries the resolved defaults for the preview
        $settings = Setting::where('setting_type', HighDemandAlertService::SETTING_TYPE)
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        return view('admin.website_management.high_demand_alert.index', [
            'settings' => $settings,
            'config'   => $alert->config(),
        ]);
    }
}
