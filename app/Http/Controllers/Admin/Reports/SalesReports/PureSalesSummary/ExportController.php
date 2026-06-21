<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary;

use App\Http\Controllers\Controller;
use App\Services\Reports\PureSalesSummaryReport;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private PureSalesSummaryReport $report,
        private SalesReportingService  $reporting,
    ) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = [
            'date_range'      => $request->input('date_range', 'mtd'),
            'start_date'      => $request->input('start_date'),
            'end_date'        => $request->input('end_date'),
            'month'           => $request->input('month') ? (int) $request->input('month') : null,
            'year'            => $request->input('year')  ? (int) $request->input('year')  : null,
            'store'           => $request->input('store'),
            'item_type'       => $request->input('item_type', 'all'),
            'category'        => $request->input('category') ? (int) $request->input('category') : null,
            'product'         => $request->input('product') ? (int) $request->input('product') : null,
            'damage_waiver'   => $request->input('damage_waiver', 'all'),
            'track_insurance' => $request->input('track_insurance', 'all'),
            'delivery'        => $request->input('delivery', 'all'),
            'shipping'        => $request->input('shipping', 'all'),
            'payment_status'  => $request->input('payment_status', 'paid'),
        ];

        $rows        = $this->report->exportData($filters);
        $kpis        = $this->report->kpis($filters);
        $periodLabel = $this->reporting->dateRangeLabel($filters);
        $filename    = 'pure-sales-summary-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $kpis, $periodLabel) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Transaction Date',
                'Transaction #',
                'Store',
                'Customer',
                'Product',
                'Category',
                'Item Type',
                'Qty',
                'Unit Price',
                'Discount',
                'Delivery Fee',
                'Extended Amount',
                'Tax',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->transaction_date,
                    $row->transaction_number,
                    $row->store_name,
                    $row->customer_name,
                    $row->product_name,
                    $row->category_name,
                    $row->item_type,
                    $row->quantity,
                    number_format($row->unit_price, 2),
                    number_format($row->discount, 2),
                    number_format($row->delivery_fee, 2),
                    number_format($row->extended_amount, 2),
                    number_format($row->tax, 2),
                ]);
            }

            // ── KPI Summary block ──────────────────────────────────────────────────
            // Provides a reconciliation anchor so totals in this export match the KPI cards.
            // Account payments are not representable as order-product line rows, so they appear here.
            fputcsv($out, []);
            fputcsv($out, ['PERIOD SUMMARY', $periodLabel]);
            fputcsv($out, ['Metric', 'Amount']);
            fputcsv($out, ['Gross Sales',             '$' . number_format($kpis['gross_sales'],     2)]);
            fputcsv($out, ['Tax Collected',           '$' . number_format($kpis['tax_collected'],   2)]);
            fputcsv($out, ['Refunds',                '-$' . number_format($kpis['refunds'],         2)]);
            fputcsv($out, ['Discounts',              '-$' . number_format($kpis['discounts'],       2)]);
            fputcsv($out, ['Net Sales',               '$' . number_format($kpis['net_sales'],       2)]);
            fputcsv($out, ['Total Collected',         '$' . number_format($kpis['total_collected'], 2)]);
            if (in_array($kpis['payment_status'], ['paid', 'all', 'account'])) {
                fputcsv($out, ['Account Payments Received', '$' . number_format($kpis['account_payments_received'], 2)]);
                fputcsv($out, ['Total Account Payments',    '$' . number_format($kpis['total_account_payments'],    2)]);
            }
            fputcsv($out, ['Delivery Revenue',        '$' . number_format($kpis['delivery_revenue'],          2)]);
            fputcsv($out, ['Damage Waiver Revenue',   '$' . number_format($kpis['damage_waiver_revenue'],     2)]);
            fputcsv($out, ['Track Insurance Revenue', '$' . number_format($kpis['track_insurance_revenue'],   2)]);
            fputcsv($out, ['Tire Insurance Revenue',  '$' . number_format($kpis['tire_insurance_revenue'],    2)]);
            fputcsv($out, ['Transaction Count',       $kpis['transaction_count']]);
            fputcsv($out, ['Average Ticket',          '$' . number_format($kpis['average_ticket'],            2)]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
