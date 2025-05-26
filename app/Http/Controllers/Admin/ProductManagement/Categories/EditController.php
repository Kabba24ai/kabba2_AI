<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
        $objProduct = ProductCategory::where('unique_id', $unique_id)->firstOrFail();

        $categories = ProductCategory::whereNull('parent_id')->where('unique_id', '!=', $unique_id)->order()->pluck('title', 'id');

        $category_tree = ProductCategory::with('childCategories')->whereNull('parent_id')->get();

        return view('admin.product_management.categories.edit', [
            'objProduct' => $objProduct,
            'categories' => $categories,
            'category_tree' => $category_tree,
        ]);
    }
}
