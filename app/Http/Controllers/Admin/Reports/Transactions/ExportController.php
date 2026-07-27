<?php

namespace App\Http\Controllers\Admin\Reports\Transactions;

use App\Http\Controllers\Controller;
use App\Services\Reports\Transactions\TransactionReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the Transaction Report as CSV (opens directly in Excel). Read-only.
 */
class ExportController extends Controller
{
    public function __construct(private TransactionReport $report) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = [
            'date_range' => 'custom',
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'store'      => $request->input('store'),
        ];

        $columns  = TransactionReport::COLUMNS;
        $rows     = $this->report->rows($filters);
        $filename = 'transaction-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($col) => $row[$col] ?? '', $columns));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
