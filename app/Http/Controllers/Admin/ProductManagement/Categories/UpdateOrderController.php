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
        $ids = $request->input('ids');
        foreach ($ids as $index => $id) {
            ProductCategory::where('id', $id)->update(['sort_order' => $index]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
        ]);
    }
}
