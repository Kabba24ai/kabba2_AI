<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

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
    public function __invoke(Request $request)
    {
        $categories = ProductCategory::order()->get();
        $funnels = collect(); // Empty Collection
        $taxes = collect();   // Empty Collection

        return view('admin.product_management.products.create', [
            'categories' => $categories,
            'funnels' => $funnels,
            'taxes' => $taxes,
        ]);
    }
}
