<?php

namespace App\Http\Controllers\Admin\Reports\NewDamageAlerts;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // ── OrderProduct-based damage charges (from rental checklist) ─────────
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

        $records = $query->get();

        // ── CRM / manually-added damage charges (from Dashboard or Order Edit) ─
        $crmQuery = CustomerAccount::with(['customer', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Damages');

        if ($request->filled('search_name')) {
            $crmQuery->whereHas('customer', function ($q) use ($request) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
            });
        }

        if ($request->filled('search_status')) {
            $status = $request->search_status;
            if ($status === 'active') {
                $crmQuery->where(function ($q) {
                    $q->whereNull('damage_alert_status')
                      ->orWhere('damage_alert_status', 'pending');
                });
            } elseif ($status === 'completed') {
                $crmQuery->where('damage_alert_status', 'completed');
            } else {
                $crmQuery->whereRaw('0 = 1');
            }
        }

        $crmDamageRecords = $crmQuery->latest()->get();

        // ── Merge both sources into one sorted collection ────────────────────
        $perPage = 25;
        $page    = max(1, (int) $request->input('page', 1));

        $merged = $records
            ->map(fn ($r) => ['_source' => 'op',  '_sort_ts' => $r->created_at?->timestamp ?? 0, '_model' => $r])
            ->concat(
                $crmDamageRecords->map(fn ($r) => ['_source' => 'crm', '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0, '_model' => $r])
            )
            ->sortByDesc('_sort_ts')
            ->values();

        $total  = $merged->count();
        $items  = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $allDamageRecords = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->except('page'),
        ]);

        if ($request->ajax()) {
            $html = view('admin.reports.new_damage_alerts.partials._table', compact('allDamageRecords'))->render();
            return response()->json(['success' => true, 'html' => $html]);
        }

        return response()->json(['success' => false], 405);
    }
}
