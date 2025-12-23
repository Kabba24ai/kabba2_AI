<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SalesFunnel;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {

            $query = SalesFunnel::with('category')->orderBy('funnel_name');

            $perPage = $request->input('per_page', 10);
            $funnels = $query->paginate($perPage)->withQueryString();

            $html = view(
                'admin.crm.sales_funnels.partials._table',
                compact('funnels')
            )->render();

            return response()->json([
                'success' => true,
                'html'    => $html,
            ]);
        }

        return view('admin.crm.sales_funnels.index');
    }
}
