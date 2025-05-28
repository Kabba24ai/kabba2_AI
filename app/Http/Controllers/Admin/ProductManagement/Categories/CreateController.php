<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductCategory;

class CreateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(?int $categoryId = null)
    {

        $categories = ProductCategory::whereNull('parent_id')->order()->pluck('title', 'id');

        $category_tree = ProductCategory::with('childCategories')->whereNull('parent_id')->get();

        return view('admin.product_management.categories.create', [
            'categories' => $categories,
            'categoryId' => $categoryId,
            'category_tree' => $category_tree,
        ]);
    }
}
