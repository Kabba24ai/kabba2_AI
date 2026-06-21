<?php

namespace App\Http\Controllers\Admin\Reports\SalesTax;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\Order;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Stores\Store;
use App\Helpers\CustomHelper;
use App\Services\Reports\SalesTaxReportEngine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __construct(private SalesTaxReportEngine $engine) {}

    public function __invoke(Request $request)
    {
        try {
            $filters = [
                'month_range'    => $request->input('month_range')  ?: null,
                'start_date'     => $request->input('start_date')   ?: null,
                'end_date'       => $request->input('end_date')      ?: null,
                'store'          => $request->input('store')         ?: null,
                'payment_method' => $request->input('payment_method') ?: null,
            ];

            // ── Three independent transaction streams ──────────────────────────
            //
            // Stream A: sales rows   — anchored on orders.order_date
            // Stream B: refund rows  — anchored on COALESCE(refunded_at, payment_datetime, created_at)
            // Stream C: account rows — anchored on customer_accounts.date
            //
            // Stream B is independent: a June refund on a May order appears
            // in June's report, not May's.

            $reportRows = $this->engine->salesRows($filters)
                ->concat($this->engine->refundRows($filters))
                ->concat($this->engine->accountRows($filters))
                ->sortByDesc(fn($row) => $row->date)
                ->filter(fn($row) => $row->tax_amount != 0)
                ->values();

            // ── Extra charges (anchored on created_at — separate system) ───────
            [$start, $end] = $this->engine->resolveDateRange($filters);

            $orderExtraCharges = OrderExtraCharges::query()
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
                ->get();

            // ── KPI stats ─────────────────────────────────────────────────────
            // All three streams use transaction-date accounting. Refund rows carry
            // negative grand_total, subtotal, and tax_amount, so summing across all
            // rows automatically produces net figures — no separate refund subtraction needed.

            $extraChargesTotalRaw = $orderExtraCharges->sum('amount');

            // F3: sum actual stored tax_amount values (positive for sales, negative for refunds)
            $netTaxAmount  = $reportRows->sum(fn($row) => $row->tax_amount);

            // F2: use subtotal sum for taxable revenue base (excludes the tax component)
            $taxableSubtotal = $reportRows->sum(fn($row) => $row->subtotal);

            // Net grand total across all taxed rows
            $reportRowsTotal = $reportRows->sum(fn($row) => $row->grand_total);

            // Relationship that must hold: taxableSubtotal + netTaxAmount === reportRowsTotal
            // (grand_total = subtotal + tax_amount, so Σgrand = Σsubtotal + Σtax)

            $salesTaxCollected        = CustomHelper::formatCurrency($netTaxAmount);
            $taxableRevenue           = CustomHelper::formatCurrency($taxableSubtotal);
            $taxFreeRevenue           = CustomHelper::formatCurrency($extraChargesTotalRaw);
            $totalCollectedAllSources = CustomHelper::formatCurrency($extraChargesTotalRaw + $reportRowsTotal);
            $totalRevenue             = CustomHelper::formatCurrency($reportRowsTotal);

            // ── Pagination ────────────────────────────────────────────────────
            $perPage  = $request->get('per_page', 30);
            $page     = $request->get('page', 1);
            $total    = $reportRows->count();
            $items    = $reportRows->slice(($page - 1) * $perPage, $perPage)->values();

            $paginated = new LengthAwarePaginator($items, $total, $perPage, $page, [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]);

            // ── AJAX response ─────────────────────────────────────────────────
            if ($request->ajax()) {
                $orders = $paginated;
                $html   = view('admin.reports.sales_tax.partials._table', compact('orders'))->render();

                return response()->json([
                    'success' => true,
                    'html'    => $html,
                    'stats'   => [
                        'totalCollectedAllSources' => $totalCollectedAllSources,
                        'totalRevenue'             => $totalRevenue,
                        'taxFreeRevenue'           => $taxFreeRevenue,
                        'taxableRevenue'           => $taxableRevenue,
                        'salesTaxCollected'        => $salesTaxCollected,
                    ],
                ]);
            }

            // ── Month dropdown ────────────────────────────────────────────────
            $allMonths = Order::selectRaw('YEAR(order_date) as year, MONTH(order_date) as month')
                ->groupBy('year', 'month')
                ->get()
                ->concat(
                    CustomerAccount::selectRaw('YEAR(date) as year, MONTH(date) as month')
                        ->where('type', 'payment')
                        ->groupBy('year', 'month')
                        ->get()
                )
                ->unique(fn($item) => $item->year . '-' . $item->month)
                ->sortByDesc(fn($item) => $item->year . str_pad($item->month, 2, '0', STR_PAD_LEFT))
                ->take(12)
                ->values();

            $availableMonths = $allMonths->map(fn($item) => [
                'value' => "{$item->year}-" . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'label' => 'Pay for '
                    . Carbon::create($item->year, $item->month, 1)->format('M 1')
                    . ' - '
                    . Carbon::create($item->year, $item->month, 1)->endOfMonth()->format('M d'),
            ]);

            return view('admin.reports.sales_tax.index', [
                'orders'                  => $paginated,
                'stores'                  => Store::all(),
                'availableMonths'         => $availableMonths,
                'totalRevenue'            => $totalRevenue,
                'totalCollectedAllSources'=> $totalCollectedAllSources,
                'taxFreeRevenue'          => $taxFreeRevenue,
                'taxableRevenue'          => $taxableRevenue,
                'salesTaxCollected'       => $salesTaxCollected,
            ]);

        } catch (\Throwable $e) {
            Log::info('Sales Tax Report Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
