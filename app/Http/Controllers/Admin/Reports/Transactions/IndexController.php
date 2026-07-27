<?php

namespace App\Http\Controllers\Admin\Reports\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\Transactions\TransactionReport;
use Illuminate\Http\Request;

/**
 * Transaction Report — general financial listing of Kabba transactions for a
 * period, with online viewing and an optional CSV/Excel export. Read-only; no
 * gateway or reconciliation concepts.
 */
class IndexController extends Controller
{
    public function __construct(private TransactionReport $report) {}

    public function __invoke(Request $request)
    {
        $filters = [
            'date_range' => 'custom',
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'store'      => $request->input('store'),
        ];

        $hasRun = $request->has('start_date') || $request->has('end_date');
        $rows   = $hasRun ? $this->report->rows($filters) : collect();

        return view('admin.reports.transactions.index', [
            'stores'  => Store::orderBy('store_name')->get(['id', 'store_name']),
            'filters' => $filters,
            'columns' => TransactionReport::COLUMNS,
            'rows'    => $rows,
            'totals'  => $this->report->totals($rows),
            'hasRun'  => $hasRun,
        ]);
    }
}
