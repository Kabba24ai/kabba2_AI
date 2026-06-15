<?php

namespace App\Http\Controllers\Admin\Reports\NewDamageAlerts;

use App\Http\Controllers\Controller;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = OrderProduct::with([
            'order.customer',
            'equipment',
            'damageChargeLogs',
        ])
        ->where(function ($q) {
            $q->where('damage_charge', '>', 0)
              ->orWhereNotNull('damage_status');
        })
        ->whereHas('order')
        ->latest('id');

        if ($request->filled('search_name')) {
            $query->whereHas('order.customer', function ($q) use ($request) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
            });
        }

        if ($request->filled('search_order')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search_order . '%');
            });
        }

        if ($request->filled('search_status')) {
            $status = $request->search_status;
            if ($status === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('damage_status')
                      ->orWhereNotIn('damage_status', ['resolved', 'completed', 'uncollectible']);
                });
            } else {
                $query->where('damage_status', $status);
            }
        }

        $records = $query->paginate($request->input('per_page', 20))->withQueryString();

        if ($request->ajax()) {
            $html = view('admin.reports.new_damage_alerts.partials._table', compact('records'))->render();
            return response()->json(['success' => true, 'html' => $html]);
        }

        return response()->json(['success' => false], 405);
    }
}
