<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use App\Models\Dispatch\DispatchAiSettings;
use App\Models\Dispatch\DispatchAiTrailer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;

class AiRulesController extends Controller
{
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
            ->where('is_active', true)
            ->orderBy('truck_name')
            ->get();

        $trailers = DispatchAiTrailer::with('store')
            ->where('is_active', true)
            ->orderBy('trailer_name')
            ->get();

        $categories = ProductCategory::orderBy('title')->get();

        $equipmentRules = DispatchAiEquipmentRule::with('productCategory')
            ->get()
            ->keyBy('product_category_id');

        $stores = Store::active()->orderBy('store_name')->get();

        $hitchTypes = ['Bumper Pull', 'Gooseneck', '5th Wheel', 'Pintle Hitch'];

        return view('admin.order_management.dispatch.ai_rules.index', compact(
            'settings',
            'drivers',
            'driverCapabilities',
            'trucks',
            'trailers',
            'categories',
            'equipmentRules',
            'stores',
            'hitchTypes',
        ));
    }
}
