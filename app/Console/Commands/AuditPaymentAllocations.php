<?php

namespace App\Console\Commands;

use App\Services\Orders\PaymentAllocationService;
use Illuminate\Console\Command;

/**
 * Phase 3D — Refund Project Completion and Release Hardening.
 *
 * Read-only integrity sweep over order_payment_refund_allocations and its
 * related order_payments rows — see
 * PaymentAllocationService::auditIntegrity() for what each figure means.
 * This command never writes anything; it exists to be run periodically
 * (or before/after a deploy) to catch drift between what the allocation
 * schema should look like and what it actually contains.
 *
 * Exit code is nonzero only when a genuine integrity failure is found
 * (broken parent pointers, split mismatches, allocation-total mismatches,
 * a refund exceeding its original payment, a negative raw remaining
 * balance, a refund marked Completed whose allocations disagree, an
 * orphan allocation, a duplicate gateway refund id or idempotency token,
 * a retained fee exceeding its configured lifetime cap, or negative
 * refunded tax) — informational counts like ambiguous legacy refunds or
 * incomplete refund operations are expected, known conditions and never
 * fail the command on their own.
 */
class AuditPaymentAllocations extends Command
{
    protected $signature = 'payments:audit
        {--detail : Show affected order/payment IDs for every issue found}';

    protected $description = 'Audit order_payment_refund_allocations and order_payments for integrity issues.';

    public function handle(): int
    {
        $this->info('Auditing payment allocations...');

        $result = PaymentAllocationService::auditIntegrity();
        $summary = $result['summary'];
        $issues = $result['issues'];

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Refund events', $summary['refund_events']],
                ['Successful allocations', $summary['successful_allocations']],
                ['Pending allocations', $summary['pending_allocations']],
                ['Failed allocations', $summary['failed_allocations']],
                ['Superseded allocations', $summary['superseded_allocations']],
                ['Unallocated refunds (no allocation rows yet)', $summary['unallocated_refunds']],
                ['Ambiguous legacy refunds', $summary['ambiguous_legacy_refunds']],
                ['Incomplete refund operations', $summary['incomplete_refund_operations']],
                ['— Broken parent pointers', $summary['broken_parent_pointers']],
                ['— Allocation total mismatches', $summary['allocation_total_mismatches']],
                ['— Base/tax/fee split mismatches', $summary['split_mismatches']],
                ['— Refunds exceeding original payment', $summary['refunds_exceeding_original']],
                ['— Negative remaining-refundable balances', $summary['negative_remaining_balances']],
                ['— Refund operation status disagreements', $summary['operation_status_disagreements']],
                ['— Orphan allocations', $summary['orphan_allocations']],
                ['— Duplicate gateway refund IDs', $summary['duplicate_gateway_refund_ids']],
                ['— Duplicate idempotency tokens', $summary['duplicate_idempotency_tokens']],
                ['— Retained fee exceeding lifetime max', $summary['fee_exceeds_lifetime_max']],
                ['— Negative refunded tax', $summary['negative_refunded_tax']],
            ]
        );

        if ($result['has_integrity_failures']) {
            $this->newLine();
            $this->error('Integrity failures found.');

            if ($this->option('detail')) {
                foreach ($issues as $category => $rows) {
                    if (empty($rows)) {
                        continue;
                    }

                    $this->newLine();
                    $this->warn(str($category)->replace('_', ' ')->title());
                    $this->table(array_keys($rows[0]), array_map(fn ($r) => array_values($r), $rows));
                }
            } else {
                $this->line('Re-run with --detail to see affected order/payment IDs.');
            }
        } else {
            $this->newLine();
            $this->info('No integrity failures found.');
        }

        return $result['has_integrity_failures'] ? self::FAILURE : self::SUCCESS;
    }
}
