<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SalesFunnelCategory;
use App\Models\Customers\SmsCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {

            $query = SalesFunnel::with('category', 'steps', 'steps.smsMessage')->withCount('steps')->orderBy('funnel_name');

            $query->when($request->input('category_id'), function ($q, $categoryId) {
                if ($categoryId === 'unassigned') {
                    $q->whereNull('sales_funnel_category_id');
                } else {
                    $q->where('sales_funnel_category_id', $categoryId);
                }
            });

            $perPage = $request->input('per_page', 30);
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

        $categories = SalesFunnelCategory::has('funnels')->withCount('funnels')->orderBy('category_name')->get();
        $queryFunnel = SalesFunnel::query();
        $totalFunnels = $queryFunnel->count();
        $unassignedFunnels = $queryFunnel->whereNull('sales_funnel_category_id')->count();

        $smsCategories = SmsCategory::orderBy('name')->get();
        return view('admin.crm.sales_funnels.index', compact('categories', 'totalFunnels', 'unassignedFunnels', 'smsCategories'));
    }
}
