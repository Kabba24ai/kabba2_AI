<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SmsBroadcast;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        
        $query = SmsBroadcast::with('category')
         ->where('status', ['created'])
        ->orderBy('name', 'ASC');

        // Filter: Category
        if ($request->filled('sms_created_bro_filter_category') && $request->sms_created_bro_filter_category !== 'All Categories') {
            $query->where('sms_cat_id', $request->sms_created_bro_filter_category);
        }

        // Filter: Name search
        if ($request->filled('sms_created_bro_Search_name')) {
            $query->where('name', 'LIKE', "%{$request->sms_created_bro_Search_name}%");
        }

        // Pagination like Customers
        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all'
            ? max(1, $query->count())
            : (int) $perPage;

        $broadcasts = $query->paginate($perPageVal)->withQueryString();

        // Return table view fragment for AJAX
        $html = view('admin.crm.message_management.partials._sms_created_broadcast_table', [
            'broadcasts' => $broadcasts
        ])->render();

        return response()->json([
            'html' => $html,
            'total' => $broadcasts->total(),
        ]);
    }
}
