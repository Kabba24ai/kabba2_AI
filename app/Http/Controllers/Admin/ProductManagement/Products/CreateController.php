<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductOption;
use App\Models\TermsAndConditions\Terms;

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
        $funnels = collect(); // Empty Collection
        $terms = Terms::product()->orderByTitle()->published()->pluck('title', 'id');
        $terms->prepend('Select a term...', '');
        $productOptions = ProductOption::orderBy('name')->get();
        $products = Product::published()->orderBy('product_name')->get();
        $productSettings = ConfigurationHelper::getSettings('Product Settings');

        return view('admin.product_management.products.create', [
            'categoryTree' => $categoryTree,
            'funnels' => $funnels,
            'terms' => $terms,
            'productOptions' => $productOptions,
            'products' => $products,
            'productSettings' => $productSettings,
        ]);
    }
}
