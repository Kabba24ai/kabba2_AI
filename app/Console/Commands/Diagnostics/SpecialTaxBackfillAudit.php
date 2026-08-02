<?php

namespace App\Console\Commands\Diagnostics;

use App\Services\Orders\SpecialTaxColumnBackfill;
use Illuminate\Console\Command;

/**
 * Audit for the special-tax / added-fees backfill.
 *
 * STRICTLY READ-ONLY. This command calls only
 * {@see SpecialTaxColumnBackfill::analyze()} — every operation beneath it is
 * a SELECT. There is no create/save/update/delete, no migration or artisan
 * call, no queue dispatch, no cache write, no HTTP call. It is safe against a
 * production database and produces identical output when run twice.
 *
 * Run it BEFORE migrating to see exactly what the backfill would write and
 * which rows it would decline, and AFTER migrating to confirm the population
 * of exceptions is what was reported. The migration performs the identical
 * reconstruction, so the two cannot disagree.
 */
class SpecialTaxBackfillAudit extends Command
{
    protected $signature = 'diagnostics:special-tax-backfill-audit {--show-ids : List the order ids in each exception category}';

    protected $description = 'Read-only audit of the special-tax/added-fees column backfill (writes nothing)';

    public function handle(): int
    {
        $this->info('Analyzing orders — read-only, nothing will be written.');

        $audit = SpecialTaxColumnBackfill::analyze();
        $counts = $audit['counts'];

        $this->newLine();
        $this->line("Orders examined: {$audit['orders_examined']}");
        $this->line("Lines examined:  {$audit['lines_examined']}");
        $this->newLine();

        $this->table(
            ['Category', 'Orders', 'Handling'],
            [
                [SpecialTaxColumnBackfill::RECONSTRUCTED, $counts[SpecialTaxColumnBackfill::RECONSTRUCTED], 'Backfilled with reconstructed nonzero components'],
                [SpecialTaxColumnBackfill::NO_COMPONENTS, $counts[SpecialTaxColumnBackfill::NO_COMPONENTS], 'Backfilled as explicit 0.00 (residual proven zero)'],
                [SpecialTaxColumnBackfill::LINELESS, $counts[SpecialTaxColumnBackfill::LINELESS], 'Order-level 0.00 — no lines AND residual proven zero'],
                [SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED, $counts[SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED], 'NOT WRITTEN — snapshot unreadable and residual nonzero'],
                [SpecialTaxColumnBackfill::UNRECONCILED, $counts[SpecialTaxColumnBackfill::UNRECONCILED], 'NOT WRITTEN — components disagree with residual'],
                [SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED, $counts[SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED], 'NOT WRITTEN — no lines, but residual is nonzero'],
            ]
        );

        $this->newLine();
        $this->line('"No lines" does not by itself prove "no special tax or fees" — it only means there is');
        $this->line('no line to attribute a component to. A line-less order is zeroed ONLY when its residual');
        $this->line('is also exactly zero; otherwise it is reported separately, never grouped with the safe ones.');

        $skipped = $counts[SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED]
            + $counts[SpecialTaxColumnBackfill::UNRECONCILED]
            + $counts[SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED];

        if ($skipped === 0) {
            $this->newLine();
            $this->info('DEPLOYMENT GATE PASSED — no exceptions. Every order reconciles exactly.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error("DEPLOYMENT GATE FAILED — {$skipped} order(s) would be left at the column default.");
        $this->warn('Do NOT run the production migration. Preserve this output, inspect the listed order');
        $this->warn('ids, classify each transaction shape, and add an explicit reconstruction or exclusion');
        $this->warn('rule before re-running.');
        $this->newLine();
        $this->line('These orders already fail HistoricalTaxBasisResolver today, and because its');
        $this->line('reconciliation is exact they keep failing for the same reason afterwards — a useful');
        $this->line('safety net, but NOT an acceptable planned outcome. Default-zero columns are not');
        $this->line('acceptance of these records: a migration that knowingly leaves financially active');
        $this->line('orders unreadable to refund reconstruction requires explicit review.');

        if ($this->option('show-ids')) {
            foreach ($audit['exceptions'] as $category => $rows) {
                if ($rows === []) {
                    continue;
                }

                $this->newLine();
                $this->line("<comment>{$category}</comment>");
                $this->table(
                    ['Order', 'Residual (cents)', 'Reconstructed (cents)'],
                    array_map(fn ($r) => [$r['order_id'], $r['residual_cents'], $r['components_cents']], $rows)
                );
            }
        } else {
            $this->line('Re-run with --show-ids to list them.');
        }

        return self::SUCCESS;
    }
}
