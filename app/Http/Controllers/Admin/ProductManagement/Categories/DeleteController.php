<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
        $objProductCategory = ProductCategory::where('unique_id', $unique_id)->firstOrFail();
        $objProductCategory->delete();

        flash('Product category deleted successfully.')->success();
        return redirect()->route('admin.product-management.categories.create');
    }
}
