<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use App\Models\Dispatch\DispatchAiSettings;
use App\Models\Dispatch\DispatchAiTrailer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Stores\Store;
use App\Services\DispatchAI\DispatchAIService;

class AiRulesController extends Controller
{
    public const TRUCK_TYPES = [
        'Any',
        '1/2 Ton',
        '3/4 Ton',
        '1 Ton',
        '1 Ton Dually',
        '2 Ton Dually',
        'Medium Duty',
        '26K Rollback',
        '30 Series Rollback',
        '40 Series Rollback',
        'Lowboy',
    ];

    public const TRAILER_TYPES = [
        'Bumper Pull',
        'Pintle Hitch',
        'Gooseneck',
    ];

    public function __invoke()
    {
        $settings = DispatchAiSettings::instance();

        $drivers = User::active()
            ->where('is_driver', true)
            ->orderBy('first_name')
            ->get();

        $driverCapabilities = DispatchAiDriverCapability::with('homeStore')
            ->whereIn('user_id', $drivers->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $trucks = DispatchAiTruck::with('store')
            ->orderBy('is_active', 'desc')
            ->orderBy('truck_name')
            ->get();

        // Equipment tab: only show columns for truck types that have at least one active truck.
        // Maintain canonical TRUCK_TYPES order. Fall back to full list if none registered yet.
        $activeTruckTypeSet = DispatchAiTruck::where('is_active', true)
            ->whereNotNull('truck_type')
            ->pluck('truck_type')
            ->unique()
            ->all();

        $activeTruckTypes = array_values(array_filter(
            self::TRUCK_TYPES,
            fn ($t) => in_array($t, $activeTruckTypeSet),
        ));

        if (empty($activeTruckTypes)) {
            $activeTruckTypes = self::TRUCK_TYPES;
        }

        $trailers = DispatchAiTrailer::with('store')
            ->orderBy('is_active', 'desc')
            ->orderBy('trailer_name')
            ->get();

        // Equipment grouped by category for the Equipment tab
        $equipmentByCategory = Equipment::with('productCategory')
            ->whereNotNull('product_category_id')
            ->whereNull('deleted_at')
            ->orderBy('equipment_name')
            ->get()
            ->groupBy('product_category_id')
            ->sortBy(fn ($group) => strtolower($group->first()->productCategory?->title ?? 'zzz'));

        $equipmentIds = Equipment::whereNotNull('product_category_id')
            ->whereNull('deleted_at')
            ->pluck('id');

        $equipmentRules = DispatchAiEquipmentRule::whereIn('equipment_id', $equipmentIds)
            ->get()
            ->keyBy('equipment_id');

        $stores = Store::active()->orderBy('store_name')->get();

        $truckTypes       = self::TRUCK_TYPES;
        $trailerTypes     = self::TRAILER_TYPES;
        $hitchTypes       = self::TRAILER_TYPES; // alias used by trailer fields partial

        // AI Policy tab data
        $settingsArr = [
            'prefer_same_driver_for_returns' => $settings->prefer_same_driver_for_returns,
            'allow_early_delivery'           => $settings->allow_early_delivery,
            'route_minimize_miles'           => $settings->route_minimize_miles,
            'route_batch_nearby_deliveries'  => $settings->route_batch_nearby_deliveries,
            'route_batch_nearby_pickups'     => $settings->route_batch_nearby_pickups,
            'route_keep_driver_near_home'    => $settings->route_keep_driver_near_home,
        ];
        $defaultPolicy = DispatchAIService::defaultPolicy($settingsArr);

        // Build the merged (overrides applied) policy for the preview pane
        $mergedPolicy = $defaultPolicy;
        foreach ($settings->policy_overrides ?? [] as $key => $override) {
            if (!isset($mergedPolicy[$key])) {
                continue;
            }
            if (!empty($override['detail'])) {
                $mergedPolicy[$key]['detail'] = $override['detail'];
            }
            foreach (['driver_lock', 'priority_lock', 'no_double_book', 'fabrication'] as $sub) {
                if (!empty($override[$sub])) {
                    $mergedPolicy[$key][$sub] = $override[$sub];
                }
            }
        }
        $policyPreviewJson = json_encode($mergedPolicy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('admin.order_management.dispatch.ai_rules.index', compact(
            'settings',
            'drivers',
            'driverCapabilities',
            'trucks',
            'trailers',
            'equipmentByCategory',
            'equipmentRules',
            'stores',
            'truckTypes',
            'activeTruckTypes',
            'trailerTypes',
            'hitchTypes',
            'defaultPolicy',
            'policyPreviewJson',
        ));
    }
}
