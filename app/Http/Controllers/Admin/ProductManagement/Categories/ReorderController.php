<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Cms\Faq\FaqCategory;


use App\Models\ProductManagement\ProductCategory;

class ReorderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $category_list = ProductCategory::whereNull('parent_id')->order()->pluck('title','id');
        $data = ['category_list' => $category_list ];

        if ($request->has('parent_id') && $request->get('parent_id') > 0) {
            $product_category_item = ProductCategory::where('id', $request->get('parent_id'))->firstOrFail();
            $data['list'] = $product_category_item->pageCategoriesBySortOrder;
            $data['selected_parent_id'] = $request->get('parent_id');
        }else{
            $list = ProductCategory::whereNull('parent_id')->order()->get();
            $data['list'] = $list;
        }

        return view('admin.product_management.categories.reorder', $data);
    }
}
