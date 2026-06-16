<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use Illuminate\Http\Request;

class ToggleAutoAssignController extends Controller
{
    const SETTING_TYPE = 'Schedule Assignment';
    const SETTING_NAME = 'auto_assign_enabled';

    public function __invoke(Request $request)
    {
        $setting = Setting::firstOrNew([
            'setting_type' => self::SETTING_TYPE,
            'setting_name' => self::SETTING_NAME,
        ]);

        if (!$setting->exists) {
            $setting->value_type    = 'boolean';
            $setting->setting_title = 'Auto Assign All Orders';
            $setting->setting_value = '1';
        } else {
            $setting->setting_value = $setting->setting_value === '1' ? '0' : '1';
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'enabled' => $setting->setting_value === '1',
        ]);
    }
}
