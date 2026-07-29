<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use App\Models\Dispatch\DispatchAiSettings;
use App\Models\Dispatch\DispatchAiTrailer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Dispatch\DispatchIntelligenceRule;
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

    // Driver mission tiers used by the AI planner (value => label).
    public const DRIVER_DESIGNATIONS = [
        'primary'   => 'Primary',
        'secondary' => 'Secondary',
        'alternate' => 'Alternate',
    ];

    public function __invoke()
    {
        $settings = DispatchAiSettings::instance();

        // Employee drivers (the Drivers tab) — contractors live on their own tab.
        $drivers = User::active()
            ->where('is_driver', true)
            ->where('is_contract_driver', false)
            ->orderBy('first_name')
            ->get();

        // External contract drivers (their own tab).
        $contractDrivers = User::active()
            ->where('is_contract_driver', true)
            ->orderBy('first_name')
            ->get();

        $driverCapabilities = DispatchAiDriverCapability::with('homeStore')
            ->whereIn('user_id', $drivers->pluck('id')->merge($contractDrivers->pluck('id')))
            ->get()
            ->keyBy('user_id');

        $driverDesignations = self::DRIVER_DESIGNATIONS;

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

        $intelligenceRuleCount        = DispatchIntelligenceRule::active()->count();
        $intelligenceRulePendingCount = DispatchIntelligenceRule::active()->pending()->count();

        // Intelligence Rules tab data (only queried when that tab is active)
        $rules        = collect();
        $ruleTypes    = [];
        $filterType   = null;
        $filterStatus = null;

        if (request('tab') === 'intelligence_rules') {
            $filterType   = request('rule_type');
            $filterStatus = request('status');

            $rules = DispatchIntelligenceRule::with(['approver:id,first_name,last_name', 'creator:id,first_name,last_name'])
                ->when($filterType,   fn ($q) => $q->where('rule_type', $filterType))
                ->when($filterStatus === 'approved', fn ($q) => $q->where('approved_by_admin', true))
                ->when($filterStatus === 'pending',  fn ($q) => $q->where('approved_by_admin', false))
                ->orderByDesc('priority')
                ->orderBy('rule_type')
                ->get();

            $ruleTypes = DispatchIntelligenceRuleType::cases();
        }

        return view('admin.order_management.dispatch.ai_rules.index', compact(
            'settings',
            'drivers',
            'contractDrivers',
            'driverCapabilities',
            'driverDesignations',
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
            'intelligenceRuleCount',
            'intelligenceRulePendingCount',
            'rules',
            'ruleTypes',
            'filterType',
            'filterStatus',
        ));
    }
}
