<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Models
use App\Models\ProductManagement\ProductCategory;

class UpdateOrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke(Request $request)
    {
        $user_item = Auth::user();
        if ($request->has('items') && count($request->get('items')) > 0) {

            ProductCategory::setNewOrder($request->get('items'));

            flash('Product categories are sorted successfully.')->success();
        }
    }
}
