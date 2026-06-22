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
                'tax_free_only'  => $request->boolean('tax_free_only'),
            ];

            // ── Three independent transaction streams ──────────────────────────
            //
            // Stream A: sales rows   — anchored on orders.order_date
            // Stream B: refund rows  — anchored on COALESCE(refunded_at, payment_datetime, created_at)
            // Stream C: account rows — anchored on customer_accounts.date
            //
            // Stream B is independent: a June refund on a May order appears
            // in June's report, not May's.

            // Merge all three streams before any filtering so KPIs see the full dataset.
            $allRows = $this->engine->salesRows($filters)
                ->concat($this->engine->refundRows($filters))
                ->concat($this->engine->accountRows($filters))
                ->sortByDesc(fn($row) => $row->date)
                ->values();

            // Tax-free revenue must be captured BEFORE the detail-table filter removes zero-tax rows.
            $taxFreeRowRevenue = $allRows
                ->filter(fn($row) => $row->tax_amount == 0)
                ->sum(fn($row) => $row->grand_total);

            // KPI base: taxable rows only (zero-tax rows are captured in $taxFreeRowRevenue above).
            $reportRows = $allRows->filter(fn($row) => $row->tax_amount != 0)->values();

            // Detail table: taxable rows by default; tax-free rows when the filter is active.
            // KPI cards always reflect the full period ($reportRows + $taxFreeRowRevenue) regardless.
            $tableRows = !empty($filters['tax_free_only'])
                ? $allRows->filter(fn($row) => $row->tax_amount == 0)->values()
                : $reportRows;

            // ── Extra charges (anchored on created_at — separate system) ───────
            [$start, $end] = $this->engine->resolveDateRange($filters);

            $orderExtraCharges = OrderExtraCharges::query()
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
                ->get();

            // ── KPI stats ─────────────────────────────────────────────────────
            // Refund rows carry negative values — summing automatically produces net figures.

            $extraChargesTotalRaw = $orderExtraCharges->sum('amount');

            // Taxed rows only (detail-table subset)
            $netTaxAmount    = $reportRows->sum(fn($row) => $row->tax_amount);
            $taxableSubtotal = $reportRows->sum(fn($row) => $row->subtotal);
            $reportRowsTotal = $reportRows->sum(fn($row) => $row->grand_total);

            // Relationship: taxableSubtotal + netTaxAmount = reportRowsTotal + Σ discount_amount
            // grand_total = subtotal + tax_amount - discount_amount (discount applied post-tax)
            // When no discounts exist: taxableSubtotal + netTaxAmount === reportRowsTotal

            $salesTaxCollected        = CustomHelper::formatCurrency($netTaxAmount);
            $taxableRevenue           = CustomHelper::formatCurrency($taxableSubtotal);
            $taxFreeRevenue           = CustomHelper::formatCurrency($taxFreeRowRevenue + $extraChargesTotalRaw);
            $totalCollectedAllSources = CustomHelper::formatCurrency($extraChargesTotalRaw + $reportRowsTotal + $taxFreeRowRevenue);
            // Gross Revenue = taxable subtotal (excl. tax) + tax-free revenue + extra charges
            $grossRevenue             = CustomHelper::formatCurrency($taxableSubtotal + $taxFreeRowRevenue + $extraChargesTotalRaw);

            // ── Pagination ────────────────────────────────────────────────────
            $perPage  = $request->get('per_page', 30);
            $page     = $request->get('page', 1);
            $total    = $tableRows->count();
            $items    = $tableRows->slice(($page - 1) * $perPage, $perPage)->values();

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
                        'grossRevenue'             => $grossRevenue,
                        'taxFreeRevenue'           => $taxFreeRevenue,
                        'taxableRevenue'           => $taxableRevenue,
                        'salesTaxCollected'        => $salesTaxCollected,
                        'totalCollectedAllSources' => $totalCollectedAllSources,
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
                'label' => Carbon::create($item->year, $item->month, 1)->format('M 1')
                    . ' - '
                    . Carbon::create($item->year, $item->month, 1)->endOfMonth()->format('M d'),
            ]);

            return view('admin.reports.sales_tax.index', [
                'orders'                  => $paginated,
                'stores'                  => Store::all(),
                'availableMonths'         => $availableMonths,
                'grossRevenue'            => $grossRevenue,
                'taxFreeRevenue'          => $taxFreeRevenue,
                'taxableRevenue'          => $taxableRevenue,
                'salesTaxCollected'       => $salesTaxCollected,
                'totalCollectedAllSources'=> $totalCollectedAllSources,
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
