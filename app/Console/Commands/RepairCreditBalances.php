<?php

namespace App\Console\Commands;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Available-Credit Consistency repair (Payment & Accounts Consistency).
 *
 * Detects — and only with --fix, corrects — customers whose stored
 * customers.available_credit_balance has drifted from the canonical
 * ledger-derived outstanding balance (CustomHelper::recomputeOutstandingBalance).
 *
 * available_credit_balance is maintained incrementally by updateCreditBalance()
 * on every charge/order/payment, so most accounts are already correct and the
 * available-credit display (max(0, credit_limit − available_credit_balance))
 * self-corrects with no data change. This command exists to detect and repair
 * any historically drifted cached balances safely.
 *
 * DRY-RUN by default — reports affected customers, before/after balances,
 * over-limit accounts, and no-ledger rows, writing NOTHING. --fix persists the
 * recomputed balance via the (decoupled, idempotent) fixTheRunningBalance().
 * Never performs a silent broad rewrite.
 */
class RepairCreditBalances extends Command
{
    protected $signature = 'credit:repair-balances
        {--fix : Persist the recomputed balance via fixTheRunningBalance() (default is a report-only dry-run)}
        {--all : Include every customer that has ledger rows, not just authorized credit customers}
        {--customer= : Limit to a single customer id}
        {--tolerance=0.01 : Absolute $ difference above which a stored balance is treated as drifted}';

    protected $description = 'Report (and optionally --fix) customers whose stored available_credit_balance disagrees with the ledger-derived outstanding balance. Dry-run by default.';

    public function handle(): int
    {
        $fix       = (bool) $this->option('fix');
        $tolerance = (float) $this->option('tolerance');

        $query = Customer::query();
        if ($this->option('customer')) {
            $query->whereKey((int) $this->option('customer'));
        } elseif ($this->option('all')) {
            $query->whereHas('accounts');
        } else {
            // Default scope: authorized credit customers (the mission's target).
            $query->where('is_credit_account', 1)->where('credit_limit', '>', 0);
        }

        $customers = $query->orderBy('id')->get();

        if ($customers->isEmpty()) {
            $this->info('No matching customers.');

            return self::SUCCESS;
        }

        $this->line(($fix ? '<fg=red>APPLY</>  ' : '<fg=yellow>DRY-RUN</> ')
            . "Scanning {$customers->count()} customer(s)…");

        $rows = [];
        $drifted = 0;
        $overLimit = 0;
        $missingLedger = 0;

        foreach ($customers as $customer) {
            $hasLedger  = $customer->accounts()->exists();
            $stored     = (float) ($customer->available_credit_balance ?? 0);
            $recomputed = CustomHelper::recomputeOutstandingBalance($customer->id);
            $diff       = round($recomputed - $stored, 2);
            $limit      = (float) ($customer->credit_limit ?? 0);
            $isOver     = $customer->is_credit_account == 1 && $limit > 0 && $recomputed > $limit;

            if (! $hasLedger) {
                $missingLedger++;
            }
            if ($isOver) {
                $overLimit++;
            }

            if (abs($diff) <= $tolerance) {
                continue; // in sync — nothing to repair
            }
            $drifted++;

            $availBefore = max(0.0, round($limit - $stored, 2));
            $availAfter  = max(0.0, round($limit - $recomputed, 2));

            if ($fix) {
                CustomHelper::fixTheRunningBalance($customer->id);
            }

            $rows[] = [
                $customer->id,
                Str::limit($customer->full_name ?? '—', 22),
                $customer->is_credit_account == 1 ? 'Y' : 'n',
                number_format($limit, 2),
                number_format($stored, 2),
                number_format($recomputed, 2),
                ($diff >= 0 ? '+' : '') . number_format($diff, 2),
                number_format($availBefore, 2) . ' → ' . number_format($availAfter, 2),
                $isOver ? 'OVER' : (! $hasLedger ? 'NO-LEDGER' : ''),
            ];
        }

        if ($rows) {
            $this->table(
                ['ID', 'Customer', 'Cr?', 'Limit', 'Stored', 'Recomputed', 'Drift', 'Available (before → after)', 'Flag'],
                $rows
            );
        }

        $this->newLine();
        $this->info("Scanned:        {$customers->count()}");
        $this->info("Drifted:        {$drifted}" . ($fix ? ' (repaired)' : ' (dry-run — nothing written)'));
        $this->info("Over-limit:     {$overLimit}");
        $this->info("No ledger rows: {$missingLedger}");

        if (! $fix && $drifted > 0) {
            $this->newLine();
            $this->warn('Re-run with --fix to persist the recomputed balances.');
        }

        return self::SUCCESS;
    }
}
