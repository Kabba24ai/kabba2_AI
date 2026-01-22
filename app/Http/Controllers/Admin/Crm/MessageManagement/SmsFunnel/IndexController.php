<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // If not AJAX, load the page
        if (! $request->ajax()) {
            return view('admin.crm.message_management.sms_broadcast.index');
        }

        $query = SmsFunnel::with('category')
          ->whereNotIn('status', ['created'])
            ->orderBy('name', 'ASC');

        // Filter: Category
        if ($request->filled('sms_funnel_filter_category') && $request->sms_funnel_filter_category !== 'All Categories') {
            $query->where('sms_cat_id', $request->sms_funnel_filter_category);
        }

        // Filter: Name search
        if ($request->filled('sms_funnel_Search_name')) {
            $query->where('name', 'LIKE', "%{$request->sms_funnel_Search_name}%");
        }

        // Pagination like Customers
        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all'
            ? max(1, $query->count())
            : (int) $perPage;

        $smsfunnels = $query->paginate($perPageVal)->withQueryString();

        // Return table view fragment for AJAX
        $html = view('admin.crm.message_management.partials._sms_funnel_table', [
            'smsfunnels' => $smsfunnels
        ])->render();

        return response()->json([
            'html' => $html,
            'total' => $smsfunnels->total(),
        ]);
    }
}
