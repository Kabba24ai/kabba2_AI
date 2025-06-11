<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductOption;
use App\Models\TermsAndCondition\Terms;

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
        $objProduct = Product::with(['categories'])
            ->where('unique_id', $unique_id)
            ->firstOrFail();
        $categoryTree = ProductCategory::with('childCategories')->whereNull('parent_id')->orderByAdmin()->get();
        $funnels = collect(); // Empty Collection
        $terms = Terms::orderByTitle()->published()->pluck('title', 'id');
        $terms->prepend('Select a term...', '');
        $productOptions = ProductOption::orderBy('name')->get();


        return view('admin.product_management.products.edit', [
            'objProduct' => $objProduct,
            'categoryTree' => $categoryTree,
            'funnels' => $funnels,
            'terms' => $terms,
            'productOptions' => $productOptions,
        ]);
    }
}
