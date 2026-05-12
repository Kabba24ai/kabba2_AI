<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

// Models
use App\Models\Customers\SalesFunnel;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductOption;
use App\Models\TermsAndConditions\Terms;

class EditController extends Controller
{
    /**
     * Show the form for editing the specified product option.
     *
     * @param string $unique_id
     * @return View
     */
    public function __invoke(string $unique_id): View
    {
        $objProduct = Product::with(['categories', 'funnels'])
            ->where('unique_id', $unique_id)
            ->firstOrFail();
        $categoryTree = ProductCategory::with('childCategories')->whereNull('parent_id')->orderByAdmin()->get();
        $funnels = SalesFunnel::active()->orderBy('funnel_name')->pluck('funnel_name', 'id');
        $terms = Terms::product()->orderByTitle()->published()->pluck('title', 'id');
        $terms->prepend('Select a term...', '');
        $productOptions = ProductOption::orderBy('name')->get();
        $productSettings = ConfigurationHelper::getSettings('Product Settings');
        $allocatedHoursSettings = ConfigurationHelper::getSettings('Allocated Hours Settings');
        $priceRateMultiplierSettings = ConfigurationHelper::getSettings('Price Rate Multiplier Settings');

        return view('admin.product_management.products.edit', [
            'objProduct' => $objProduct,
            'categoryTree' => $categoryTree,
            'funnels' => $funnels,
            'terms' => $terms,
            'productOptions' => $productOptions,
            'productSettings' => $productSettings,
            'allocatedHoursSettings' => $allocatedHoursSettings,
            'priceRateMultiplierSettings' => $priceRateMultiplierSettings,
        ]);
    }
}
