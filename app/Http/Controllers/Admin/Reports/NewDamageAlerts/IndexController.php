<?php

namespace App\Http\Controllers\Admin\Reports\NewDamageAlerts;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->ajax()) {
            return response()->json(['success' => false], 405);
        }

        try {
            // ── OrderProduct-based damage charges (from rental checklist) ─────────
            $query = OrderProduct::with([
                'order:id,customer_id,unique_id,order_number',
                'order.customer:id,first_name,last_name,phone,unique_id',
                'equipment:id,equipment_name',
                'damageChargeLogs:id,order_product_id,change_amount',
                'billingCharges' => fn ($q) => $q->select(['id', 'order_product_id', 'billing_charge_type', 'amount', 'status'])
                    ->where('billing_charge_type', 'damage')
                    ->whereNull('customer_account_id'),
            ])
            ->where(function ($q) {
                $q->where('damage_charge', '>', 0)
                  ->orWhereNotNull('damage_status');
            })
            ->whereHas('order')
            // Exclude OPs that already have a CA ledger record — those appear in the CRM source below
            ->whereDoesntHave('customerAccountCharges', fn ($q) => $q->where('reason', 'Damages'))
            ->latest('id');

            if ($request->filled('search_name')) {
                $query->whereHas('order.customer', function ($q) use ($request) {
                    $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
                });
            }

            if ($request->filled('search_order')) {
                $search = trim($request->search_order);
                $query->whereHas('order', function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('reference_order_number', 'like', "%{$search}%");
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

            $records = $query
                ->select([
                    'id',
                    'order_id',
                    'equipment_id',
                    'damage_charge',
                    'damage_status',
                    'created_at',
                ])
                ->get();

            // ── CRM / manually-added damage charges (from Dashboard or Order Edit) ─
            $crmQuery = CustomerAccount::with([
                'customer:id,first_name,last_name,phone,unique_id',
                'order:id,unique_id,order_number',
            ])
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

            $crmDamageRecords = $crmQuery
                ->select([
                    'id',
                    'customer_id',
                    'order_id',
                    'amount',
                    'sales_tax',
                    'sales_tax_type',
                    'notes',
                    'damage_alert_status',
                    'date',
                    'created_at',
                ])
                ->latest()
                ->get();

            // ── Merge both sources into one sorted collection ────────────────────
            $perPage = $request->input('per_page', 30);
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

            $html = view('admin.reports.new_damage_alerts.partials._table', compact('allDamageRecords'))->render();
            return response()->json(['success' => true, 'html' => $html]);

        } catch (\Throwable $e) {
            Log::error('NewDamageAlerts AJAX error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
