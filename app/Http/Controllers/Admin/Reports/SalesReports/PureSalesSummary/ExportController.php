<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary;

use App\Http\Controllers\Controller;
use App\Services\Reports\PureSalesSummaryReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private PureSalesSummaryReport $report) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = [
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
        ];

        $rows     = $this->report->exportData($filters);
        $filename = 'pure-sales-summary-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
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

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
