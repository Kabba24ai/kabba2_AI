{{--
    Phase 3D — Refund Reporting (mission §8).

    Compact per-refund-event detail shown alongside every refund row in
    the Reconciliation Ledger: calc type, requested vs. still-unprocessed
    amount, operation status, employee, and failure reason where
    applicable. Reuses PaymentReconciliationLedger::streamB()'s row shape
    (populated from PaymentAllocationService-backed data) rather than a
    separate report engine or duplicated query, per the mission's own
    "avoid duplicate financial sources of truth" guidance.

    Expects: $row — a Stream B ledger row (stream === 'refund'), which
    carries refund_calculation_type / refund_operation_status / employee /
    requested_amount / unprocessed_amount / failure_reason.
--}}
<div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-gray-400 dark:text-gray-500">
    @if (($row->refund_calculation_type ?? 'Standard') !== 'Standard')
        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
            {{ $row->refund_calculation_type }}
        </span>
    @endif

    @if (($row->refund_operation_status ?? 'completed') !== 'completed')
        <span class="inline-flex items-center px-1.5 py-0.5 rounded
            {{ $row->refund_operation_status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' }}">
            {{ ucwords(str_replace('_', ' ', $row->refund_operation_status)) }}
        </span>
    @endif

    @if (($row->unprocessed_amount ?? 0) > 0)
        <span class="text-red-500">Unprocessed: {{ \App\Helpers\CustomHelper::formatCurrency($row->unprocessed_amount) }}</span>
    @endif

    @if (!empty($row->employee))
        <span>By: {{ $row->employee }}</span>
    @endif

    @if (!empty($row->failure_reason))
        <span class="text-red-500 truncate max-w-[220px]" title="{{ $row->failure_reason }}">{{ $row->failure_reason }}</span>
    @endif
</div>
