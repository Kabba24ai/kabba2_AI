<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Customers\SalesFunnelCategory;

class FeachController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
         return response()->json([
            'success' => true,
            'data' => SalesFunnelCategory::orderBy('category_name')
                ->get(['id', 'category_name'])
        ]);
    }
}
