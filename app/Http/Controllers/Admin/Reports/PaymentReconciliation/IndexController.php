<?php

namespace App\Http\Controllers\Admin\Reports\PaymentReconciliation;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationExport;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationSummary;
use App\Services\Reports\PaymentReconciliation\ReconciliationFileStore;
use Illuminate\Http\Request;

/**
 * Authorize.Net Reconciliation wizard. Its sole purpose is comparing an
 * uploaded Authorize.Net settlement file against Kabba transactions, so it
 * REQUIRES a settlement file — it never runs Kabba-only (that is the separate
 * Transaction Report). Workflow: upload settlement file → select Kabba date
 * range → run → (optionally) export.
 *
 * Reuses the canonical engine unchanged: TransactionEnumerator (via
 * PaymentReconciliationExport), the parser/matcher, and the summary. The
 * uploaded file is held under a token so filtering/exporting the results does
 * not require re-uploading. Read-only.
 */
class IndexController extends Controller
{
    public function __construct(
        private PaymentReconciliationExport $export,
        private PaymentReconciliationSummary $summary,
        private ReconciliationFileStore $files,
    ) {}

    public function __invoke(Request $request)
    {
        $filters = [
            'date_range' => 'custom',
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'store'      => $request->input('store'),
        ];
        $activeFilter = $request->input('filter', 'all');

        // The settlement file is mandatory: a fresh upload, or a previously
        // uploaded file held under a token. No file → the wizard cannot run.
        [$gatewayContents, $reconToken] = $this->resolveSettlementFile($request);
        $hasFile   = $gatewayContents !== null;
        $hasRange  = !empty($filters['start_date']) && !empty($filters['end_date']);
        $ran       = $hasFile && $hasRange;

        $allRows   = $ran ? $this->export->rows($filters, $gatewayContents) : collect();
        $summary   = $this->summary->fromRows($allRows);
        $tableRows = $this->export->applyQuickFilter($allRows, $activeFilter);

        return view('admin.reports.payment_reconciliation.index', [
            'stores'        => Store::orderBy('store_name')->get(['id', 'store_name']),
            'filters'       => $filters,
            'activeFilter'  => $activeFilter,
            'filterOptions' => PaymentReconciliationExport::filterOptions(),
            'summary'       => $summary,
            'rows'          => $tableRows,
            'hasFile'       => $hasFile,
            'hasRange'      => $hasRange,
            'ran'           => $ran,
            'reconToken'    => $reconToken,
        ]);
    }

    /**
     * Resolve the settlement file from a new upload (stored + tokenized) or a
     * prior upload referenced by token.
     *
     * @return array{0: ?string, 1: ?string}  [contents, token]
     */
    private function resolveSettlementFile(Request $request): array
    {
        if ($request->hasFile('gateway_file')) {
            $contents = file_get_contents($request->file('gateway_file')->getRealPath()) ?: null;
            if ($contents === null) {
                return [null, null];
            }

            return [$contents, $this->files->store($contents)];
        }

        $token    = $request->input('recon_token');
        $contents = $this->files->retrieve($token);

        return $contents !== null ? [$contents, $token] : [null, null];
    }
}
