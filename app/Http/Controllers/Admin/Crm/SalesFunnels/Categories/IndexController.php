<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Customers\SalesFunnelCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {

            $query = SalesFunnelCategory::query();

            $perPage = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $categories = $query->latest('id')->paginate($perPageVal)->withQueryString(); // keeps filters in pagination links

            $html = view('admin.crm.sales_funnels.categories._table', compact('categories'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }
        return view('admin.crm.sales_funnels.index', );
    }
}
