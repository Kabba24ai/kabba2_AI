<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsFunnel;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // -----------------------------
        // BROADCAST QUERY + CATEGORY
        // -----------------------------
        $broadcastQuery = SmsBroadcast::query()
            ->leftJoin('sms_categories', 'sms_categories.id', '=', 'sms_broadcasts.sms_cat_id')
            ->select(
                'sms_broadcasts.id',
                'sms_broadcasts.sms_cat_id',
                'sms_broadcasts.name',
                'sms_broadcasts.description',
                'sms_broadcasts.created_at',
                'sms_categories.name as category_name',
                DB::raw("'broadcast' as type")
            )
            ->where('sms_broadcasts.status', 'created')
            ->toBase(); //  IMPORTANT

        // -----------------------------
        // FUNNEL QUERY + CATEGORY
        // -----------------------------
        $funnelQuery = SmsFunnel::query()
            ->leftJoin('sms_categories', 'sms_categories.id', '=', 'sms_funnels.sms_cat_id')
            ->select(
                'sms_funnels.id',
                'sms_funnels.sms_cat_id',
                'sms_funnels.name',
                'sms_funnels.description',
                'sms_funnels.created_at',
                'sms_categories.name as category_name',
                DB::raw("'funnel' as type")
            )
            ->where('sms_funnels.status', 'created')
            ->toBase();

        // -----------------------------
        // FILTERS (BOTH)
        // -----------------------------
        if ($request->filled('sms_created_bro_filter_category') &&
            $request->sms_created_bro_filter_category !== 'All Categories') {

            $broadcastQuery->where('sms_broadcasts.sms_cat_id', $request->sms_created_bro_filter_category);
            $funnelQuery->where('sms_funnels.sms_cat_id', $request->sms_created_bro_filter_category);
        }

        if ($request->filled('sms_created_bro_Search_name')) {
            $broadcastQuery->where('sms_broadcasts.name', 'LIKE', "%{$request->sms_created_bro_Search_name}%");
            $funnelQuery->where('sms_funnels.name', 'LIKE', "%{$request->sms_created_bro_Search_name}%");
        }

        // -----------------------------
        // UNION + PAGINATION
        // -----------------------------
        $union = $broadcastQuery->unionAll($funnelQuery);

        $perPage = (int) $request->input('per_page', 30);

        $broadcasts = DB::query()
            ->fromSub($union, 'sms_contents')
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        // -----------------------------
        // VIEW
        // -----------------------------
        $html = view('admin.crm.message_management.partials._sms_created_table', [
            'broadcasts' => $broadcasts
        ])->render();

        return response()->json([
            'html' => $html,
            'total' => $broadcasts->total(),
        ]);
    }
}
