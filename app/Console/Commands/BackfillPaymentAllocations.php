<?php

namespace App\Console\Commands;

use App\Services\Orders\PaymentAllocationService;
use Illuminate\Console\Command;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * Historical backfill: creates an order_payment_refund_allocations row for
 * every existing refund whose original payment can be resolved
 * unambiguously (see PaymentAllocationService::resolveLegacyOriginalPayment()).
 * Genuinely ambiguous refunds (multi-payment orders with no reliable
 * pointer) are left untouched and reported — never fabricated.
 *
 * Report-only (--dry-run) by default is NOT the default here — unlike
 * RepairExtensionOrphans, this command's writes are purely additive
 * (new allocation rows + a derived parent_order_payment_id) and reversible
 * (down() on the allocation table migration, or simply truncating the new
 * table), so the safer default is to actually run it; --dry-run is
 * available for a preview-only pass.
 */
class BackfillPaymentAllocations extends Command
{
    protected $signature = 'payments:backfill-allocations
        {--dry-run : Report what would be allocated without writing anything}';

    protected $description = 'Backfill order_payment_refund_allocations for existing refunds with unambiguous attribution.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Running in --dry-run mode — no rows will be written.'
            : 'Backfilling refund allocations...');

        $report = PaymentAllocationService::backfill($dryRun);

        // newly_allocated and already_allocated are reported as two
        // separate rows (rather than one combined "allocated" total)
        // specifically so a rerun is easy to audit: on a healthy rerun,
        // Newly Allocated should read 0 and Already Allocated should
        // absorb everything a prior run already handled.
        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Newly Allocated' . ($dryRun ? ' (would allocate)' : ''), $report['newly_allocated']],
                ['Already Allocated (skipped — prior run)', $report['already_allocated']],
                ['Skipped — Ambiguous (multi-payment, no reliable pointer)', $report['skipped_ambiguous']],
                ['Errors', count($report['errors'])],
            ]
        );

        if (!empty($report['errors'])) {
            $this->newLine();
            $this->error('Errors encountered:');
            $this->table(
                ['Refund order_payment_id', 'Message'],
                array_map(fn ($e) => [$e['refund_order_payment_id'], $e['message']], $report['errors'])
            );
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run complete. Re-run without --dry-run to write these allocations.'
            : 'Backfill complete. Re-running this command is safe — already-allocated refunds are skipped.');

        return empty($report['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
