<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductOption;
use App\Models\TermsAndConditions\Terms;
use App\Models\ProductManagement\Product;

class CreateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $categoryTree = ProductCategory::with('childCategories')->whereNull('parent_id')->orderByAdmin()->get();
        $funnels = SalesFunnel::active()->orderBy('funnel_name')->pluck('funnel_name', 'id');
        $terms = Terms::product()->orderByTitle()->published()->pluck('title', 'id');
        $terms->prepend('Select a term...', '');
        $productOptions = ProductOption::orderBy('name')->get();
        $products = Product::published()->orderBy('product_name')->get();
        $productSettings = ConfigurationHelper::getSettings('Product Settings');
        $allocatedHoursSettings = ConfigurationHelper::getSettings('Allocated Hours Settings');
        $priceRateMultiplierSettings = ConfigurationHelper::getSettings('Price Rate Multiplier Settings');

        $allEquipmentJson = Equipment::orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id', 'product_category_id'])
            ->map(fn($e) => [
                'id'          => $e->id,
                'label'       => $e->equipment_name . ($e->equipment_id ? ' (' . $e->equipment_id . ')' : ''),
                'category_id' => $e->product_category_id,
            ])
            ->values()
            ->toJson();

        return view('admin.product_management.products.create', [
            'categoryTree'              => $categoryTree,
            'funnels'                   => $funnels,
            'terms'                     => $terms,
            'productOptions'            => $productOptions,
            'products'                  => $products,
            'productSettings'           => $productSettings,
            'allocatedHoursSettings'    => $allocatedHoursSettings,
            'priceRateMultiplierSettings' => $priceRateMultiplierSettings,
            'allEquipmentJson'          => $allEquipmentJson,
            'equipmentOptions'          => collect(),
            'assignedEquipmentIds'      => [],
        ]);
    }
}
