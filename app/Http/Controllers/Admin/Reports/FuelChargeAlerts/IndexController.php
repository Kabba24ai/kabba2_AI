<?php

namespace App\Http\Controllers\Admin\Reports\FuelChargeAlerts;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // ── OrderProduct-based fuel charges (from rental checklist) ──────────
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

        $records = $query->get();

        // ── CRM / manually-added fuel charges (from Dashboard or Order Edit) ─
        $crmQuery = CustomerAccount::with(['customer', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge');

        if ($request->filled('search_name')) {
            $crmQuery->whereHas('customer', function ($q) use ($request) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
            });
        }

        // search_order doesn't apply to CRM records (no order_number)
        // Map status filter to the CRM column name
        if ($request->filled('search_status')) {
            $status = $request->search_status;
            if ($status === 'active') {
                $crmQuery->where(function ($q) {
                    $q->whereNull('fuel_alert_status')
                      ->orWhere('fuel_alert_status', 'pending');
                });
            } elseif ($status === 'completed') {
                $crmQuery->where('fuel_alert_status', 'completed');
            } else {
                // 'resolved' / 'uncollectible' don't exist for CRM records
                $crmQuery->whereRaw('0 = 1');
            }
        }

        $crmFuelRecords = $crmQuery->latest()->get();

        // ── Merge both sources into one sorted collection ────────────────────

        // $allFuelRecords = $records
        //     ->map(fn ($r) => ['_source' => 'op',  '_sort_ts' => $r->created_at?->timestamp ?? 0, '_model' => $r])
        //     ->concat(
        //         $crmFuelRecords->map(fn ($r) => ['_source' => 'crm', '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0, '_model' => $r])
        //     )
        //     ->sortByDesc('_sort_ts')
        //     ->values();


        $allFuelRecords = $records
    ->map(fn ($r) => [
        '_source' => 'op',
        '_sort_ts' => $r->created_at?->timestamp ?? 0,
        '_model' => $r,
    ])
    ->concat(
        $crmFuelRecords->map(fn ($r) => [
            '_source' => 'crm',
            '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0,
            '_model' => $r,
        ])
    )
    ->sortByDesc('_sort_ts')
    ->values();

$page = $request->get('page', 1);
$perPage = $request->input('per_page', 20);

$allFuelRecords = new LengthAwarePaginator(
    $allFuelRecords->forPage($page, $perPage),
    $allFuelRecords->count(),
    $perPage,
    $page,
    [
        'path' => $request->url(),
        'query' => $request->query(),
    ]
);


        if ($request->ajax()) {
            $html = view('admin.reports.fuel_charge_alerts.partials._table', compact('allFuelRecords'))->render();
            return response()->json(['success' => true, 'html' => $html]);
        }

        return response()->json(['success' => false], 405);
    }
}
