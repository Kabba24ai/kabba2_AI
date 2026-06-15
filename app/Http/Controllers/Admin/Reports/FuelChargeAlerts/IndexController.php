<?php

namespace App\Http\Controllers\Admin\Reports\FuelChargeAlerts;

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
            'fuelChargeLogs',
        ])
        ->whereNotNull('fuel_total_charge')
        ->where('fuel_total_charge', '>', 0)
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
                    $q->whereNull('fuel_charge_status')
                      ->orWhereNotIn('fuel_charge_status', ['resolved', 'completed', 'uncollectible']);
                });
            } else {
                $query->where('fuel_charge_status', $status);
            }
        }

        $records = $query->paginate($request->input('per_page', 20))->withQueryString();

        if ($request->ajax()) {
            $html = view('admin.reports.fuel_charge_alerts.partials._table', compact('records'))->render();
            return response()->json(['success' => true, 'html' => $html]);
        }

        return response()->json(['success' => false], 405);
    }
}
