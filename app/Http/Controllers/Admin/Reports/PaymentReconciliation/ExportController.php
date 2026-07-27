<?php

namespace App\Http\Controllers\Admin\Reports\PaymentReconciliation;

use App\Http\Controllers\Controller;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationExport;
use App\Services\Reports\PaymentReconciliation\ReconciliationColumns;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the Payment Reconciliation export as CSV. Columns begin with the
 * exact Authorize.Net transaction-download layout (ReconciliationColumns), then
 * Kabba + reconciliation columns, so the file diffs cleanly against a real
 * gateway download. An optional uploaded Authorize.Net export enables
 * transaction-level cross-matching; without it the export is Kabba-only.
 *
 * Read-only: this endpoint never writes to any Kabba table.
 */
class ExportController extends Controller
{
    public function __construct(private PaymentReconciliationExport $export) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = [
            'date_range' => $request->input('date_range', 'mtd'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'month'      => $request->input('month') ? (int) $request->input('month') : null,
            'year'       => $request->input('year') ? (int) $request->input('year') : null,
            'store'      => $request->input('store'),
        ];

        // Optional authoritative gateway export for cross-matching.
        $gatewayContents = null;
        if ($request->hasFile('gateway_file')) {
            $gatewayContents = file_get_contents($request->file('gateway_file')->getRealPath()) ?: null;
        }

        $columns = ReconciliationColumns::all();
        $rows    = $this->export->rows($filters, $gatewayContents);

        // Second export: exceptions only (everything not an Exact Match) — the
        // subset accounting reviews daily. Uses the canonical status filter.
        $exceptionsOnly = $request->boolean('exceptions');
        if ($exceptionsOnly) {
            $rows = $this->export->onlyExceptions($rows);
        }

        $filename = 'authorize-net-reconciliation' . ($exceptionsOnly ? '-exceptions' : '')
            . '-' . now()->format('Y-m-d') . '.csv';

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
