<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary;

use App\Helpers\CustomHelper;
use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\PureSalesSummaryReport;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(
        private PureSalesSummaryReport $report,
        private SalesReportingService  $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        $filters = $this->extractFilters($request);

        // Lightweight endpoint: category → product dropdown population
        if ($request->has('ajax_products')) {
            $products = $this->report->availableProducts($filters['category'] ?? null);
            return response()->json(['products' => $products]);
        }

        if ($request->ajax()) {
            return $this->ajaxResponse($request, $filters);
        }

        $kpis          = $this->report->kpis($filters);
        $grid          = $this->report->detailGrid($filters, $request->integer('page', 1), 50);
        $categories    = $this->report->availableCategories();
        $products      = $this->report->availableProducts($filters['category'] ?? null);
        $dateRangeLabel = $this->reporting->dateRangeLabel($filters);

        return view('admin.reports.sales_reports.pure_sales_summary.index', [
            'filters'        => $filters,
            'kpis'           => $this->formatKpis($kpis),
            'grid'           => $grid,
            'stores'         => Store::orderBy('store_name')->get(['id', 'store_name']),
            'categories'     => $categories,
            'products'       => $products,
            'dateRangeLabel' => $dateRangeLabel,
        ]);
    }

    private function ajaxResponse(Request $request, array $filters): \Illuminate\Http\JsonResponse
    {
        try {
            $tab = $request->input('tab', 'all');

            if ($tab === 'kpis') {
                $kpis = $this->formatKpis($this->report->kpis($filters));
                $html = view('admin.reports.sales_reports.pure_sales_summary.partials._kpi_cards', [
                    'kpis'           => $kpis,
                    'dateRangeLabel' => $this->reporting->dateRangeLabel($filters),
                ])->render();
                return response()->json(['success' => true, 'html' => $html, 'kpis' => $kpis]);
            }

            $grid  = $this->report->detailGrid($filters, $request->integer('page', 1), 50);
            $kpis  = $this->formatKpis($this->report->kpis($filters));
            $html  = view('admin.reports.sales_reports.pure_sales_summary.partials._detail_table', [
                'grid' => $grid,
            ])->render();
            $kpiHtml = view('admin.reports.sales_reports.pure_sales_summary.partials._kpi_cards', [
                'kpis'           => $kpis,
                'dateRangeLabel' => $this->reporting->dateRangeLabel($filters),
            ])->render();

            return response()->json([
                'success'  => true,
                'html'     => $html,
                'kpi_html' => $kpiHtml,
                'kpis'     => $kpis,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Pure Sales Summary AJAX error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'      => $request->input('date_range', 'mtd'),
            'start_date'      => $request->input('start_date'),
            'end_date'        => $request->input('end_date'),
            'store'           => $request->input('store'),
            'item_type'       => $request->input('item_type', 'all'),
            'category'        => $request->input('category') ? (int) $request->input('category') : null,
            'product'         => $request->input('product') ? (int) $request->input('product') : null,
            'damage_waiver'   => $request->input('damage_waiver', 'all'),
            'track_insurance' => $request->input('track_insurance', 'all'),
            'delivery'        => $request->input('delivery', 'all'),
            'payment_status'  => $request->input('payment_status', 'paid'),
        ];
    }

    private function formatKpis(array $kpis): array
    {
        return [
            'gross_sales'             => CustomHelper::formatCurrency($kpis['gross_sales']),
            'discounts'               => CustomHelper::formatCurrency($kpis['discounts']),
            'net_sales'               => CustomHelper::formatCurrency($kpis['net_sales']),
            'tax_collected'           => CustomHelper::formatCurrency($kpis['tax_collected']),
            'delivery_revenue'        => CustomHelper::formatCurrency($kpis['delivery_revenue']),
            'damage_waiver_revenue'   => CustomHelper::formatCurrency($kpis['damage_waiver_revenue']),
            'track_insurance_revenue' => CustomHelper::formatCurrency($kpis['track_insurance_revenue']),
            'shipping_revenue'        => CustomHelper::formatCurrency($kpis['shipping_revenue']),
            'transaction_count'         => number_format($kpis['transaction_count']),
            'average_ticket'            => CustomHelper::formatCurrency($kpis['average_ticket']),
            'account_payments_received' => CustomHelper::formatCurrency($kpis['account_payments_received']),
            'payment_status'            => $kpis['payment_status'],
            'refunds'                   => CustomHelper::formatCurrency($kpis['refunds']),
            'total_realized_revenue'    => CustomHelper::formatCurrency($kpis['total_realized_revenue']),
            // raw values for JS
            'raw'                       => $kpis,
        ];
    }
}
