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
        $categories = ProductCategory::orderByAdmin()->pluck('title', 'id');

        // Generate category tree for parent selection
        $category = $categoryId ? ProductCategory::where('id', $categoryId)->first() : null;

        $category_tree = ProductCategory::with([
            'childCategories' => function ($q) {
                $q->sortOrder();
            },
        ])
            ->whereNull('parent_id')
            ->sortOrder()
            ->get();

        return view('admin.product_management.categories.create', [
            'categoryId' => $categoryId,
            'category' => $category,
            'category_tree' => $category_tree,
            'categories' => $categories,
        ]);
    }
}
