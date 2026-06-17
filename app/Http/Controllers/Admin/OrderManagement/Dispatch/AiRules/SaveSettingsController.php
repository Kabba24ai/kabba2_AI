<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiSettings;
use Illuminate\Http\Request;

class SaveSettingsController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            // General
            'prefer_same_driver_for_returns'             => 'boolean',
            // Automation
            'ai_enabled'                                 => 'boolean',
            'cron_hour'                                  => 'required|integer|min:0|max:23',
            'cron_minute'                                => 'required|integer|min:0|max:59',
            'look_ahead_days'                            => 'required|integer|min:1|max:14',
            'auto_build_draft'                           => 'boolean',
            'allow_driver_assignment'                    => 'boolean',
            'allow_truck_assignment'                     => 'boolean',
            'allow_trailer_assignment'                   => 'boolean',
            'allow_route_optimization'                   => 'boolean',
            'allow_early_delivery'                       => 'boolean',
            // Early delivery
            'early_delivery_enabled'                     => 'boolean',
            'early_delivery_max_days'                    => 'required|integer|min:1|max:7',
            'early_delivery_window_start'                => 'nullable|date_format:H:i',
            'early_delivery_window_end'                  => 'nullable|date_format:H:i',
            'early_delivery_customer_must_approve'       => 'boolean',
            'early_delivery_prioritize_weekend_specials' => 'boolean',
            'early_delivery_reduce_friday_load'          => 'boolean',
            // Routing weights
            'route_efficiency_weight'                    => 'required|integer|min:0|max:100',
            'delivery_priority_weight'                   => 'required|integer|min:0|max:100',
            'driver_utilization_weight'                  => 'required|integer|min:0|max:100',
            // Routing prefs
            'route_minimize_miles'                       => 'boolean',
            'route_minimize_drive_time'                  => 'boolean',
            'route_batch_nearby_deliveries'              => 'boolean',
            'route_batch_nearby_pickups'                 => 'boolean',
            'route_keep_driver_near_home'                => 'boolean',
            'route_respect_delivery_windows'             => 'boolean',
        ]);

        // Checkboxes not present in POST when unchecked — default to false
        $booleans = [
            'prefer_same_driver_for_returns', 'ai_enabled', 'auto_build_draft',
            'allow_driver_assignment', 'allow_truck_assignment', 'allow_trailer_assignment',
            'allow_route_optimization', 'allow_early_delivery', 'early_delivery_enabled',
            'early_delivery_customer_must_approve', 'early_delivery_prioritize_weekend_specials',
            'early_delivery_reduce_friday_load', 'route_minimize_miles', 'route_minimize_drive_time',
            'route_batch_nearby_deliveries', 'route_batch_nearby_pickups',
            'route_keep_driver_near_home', 'route_respect_delivery_windows',
        ];
        foreach ($booleans as $key) {
            $validated[$key] = $request->boolean($key);
        }

        $settings = DispatchAiSettings::instance();
        $settings->update($validated);

        return redirect()->route('admin.order-management.dispatch.ai-rules.index', ['tab' => $request->input('tab', 'general')])
            ->with('success', 'Settings saved.');
    }
}
