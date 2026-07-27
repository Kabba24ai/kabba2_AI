<?php

namespace App\Http\Controllers\Admin\Reports\PaymentReconciliation;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationExport;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationSummary;
use Illuminate\Http\Request;

/**
 * Authorize.Net Reconciliation report page: dashboard summary + health banner +
 * quick filters + results table, with an optional Authorize.Net export upload
 * for transaction-level cross-matching. Read-only.
 *
 * The summary reflects the WHOLE period (stable totals); the quick filter only
 * narrows the table. Both derive from the canonical matcher/export — no
 * reconciliation logic is duplicated here.
 */
class IndexController extends Controller
{
    public function __construct(
        private PaymentReconciliationExport $export,
        private PaymentReconciliationSummary $summary,
    ) {}

    public function __invoke(Request $request)
    {
        $filters = [
            'date_range' => $request->input('date_range', 'mtd'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'store'      => $request->input('store'),
        ];
        $activeFilter = $request->input('filter', 'all');

        // Optional gateway export (session-less: only when uploaded this request).
        $gatewayContents = null;
        if ($request->hasFile('gateway_file')) {
            $gatewayContents = file_get_contents($request->file('gateway_file')->getRealPath()) ?: null;
        }

        // A run only executes when the operator has submitted the form — keeps
        // the initial page cheap and avoids an unbounded default query.
        $hasRun  = $request->has('start_date') || $request->has('end_date') || $request->has('filter');
        $allRows = $hasRun ? $this->export->rows($filters, $gatewayContents) : collect();

        $summary   = $this->summary->fromRows($allRows);
        $tableRows = $this->export->applyQuickFilter($allRows, $activeFilter);

        return view('admin.reports.payment_reconciliation.index', [
            'stores'        => Store::orderBy('store_name')->get(['id', 'store_name']),
            'filters'       => $filters,
            'activeFilter'  => $activeFilter,
            'filterOptions' => PaymentReconciliationExport::filterOptions(),
            'summary'       => $summary,
            'rows'          => $tableRows,
            'hasRun'        => $hasRun,
        ]);
    }
}
