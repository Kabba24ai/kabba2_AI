<?php

namespace App\Console\Commands;

use App\Enums\Billing\BillingChargeType;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\ExtensionTransactionService;
use Illuminate\Console\Command;

/**
 * Phase 1B phantom repair: finds extension transactions where one side was
 * deleted without the other (e.g. the Rental Extension charge left on parent
 * #2996 after its child order was deleted), reports them, and — only with
 * --fix — soft-deletes the surviving side through ExtensionTransactionService
 * so the repair is transactional and lands in the parent order's history.
 *
 * Deliberately narrow:
 *  - Detection uses the canonical child_order_id linkage, never the order
 *    number suffix.
 *  - Dry-run by default; --fix requires --user for audit attribution.
 *  - Paid orphans with no Kabba refund/void are NEVER auto-repaired: they
 *    are listed for manual deletion through the UI, which captures the
 *    administrative disposition. --charge limits a run to one charge.
 */
class RepairExtensionOrphans extends Command
{
    protected $signature = 'extension:repair-orphans
        {--fix : Soft-delete the surviving side of each verified orphan (default is report-only)}
        {--charge= : Limit to a single BillingCharge unique_id}
        {--user= : User id to attribute the repair to (required with --fix)}';

    protected $description = 'Report (and optionally repair) one-sided extension transactions: phantom Rental Extension charges whose child order is deleted, and extension child orders whose charge is deleted.';

    public function handle(): int
    {
        $fix   = (bool) $this->option('fix');
        $actor = null;

        if ($fix) {
            if (!$this->option('user')) {
                $this->error('--fix requires --user=<id> for audit attribution.');
                return self::FAILURE;
            }
            $actor = User::find($this->option('user'));
            if (!$actor) {
                $this->error('No user found for --user=' . $this->option('user'));
                return self::FAILURE;
            }
        }

        // ── A) Phantom charges: active extension charge, child order gone ──
        $charges = BillingCharge::where('billing_charge_type', BillingChargeType::Extension->value)
            ->when($this->option('charge'), fn ($q) => $q->where('unique_id', $this->option('charge')))
            ->get();

        $phantoms = $charges->filter(function (BillingCharge $charge) {
            if (!$charge->child_order_id) {
                return true; // never linked — flag for review
            }
            $child = Order::withTrashed()->find($charge->child_order_id);

            return !$child || $child->trashed();
        });

        // ── B) Orphan children: active extension child, charge gone ────────
        $orphanChildren = collect();
        if (!$this->option('charge')) {
            $orphanChildren = Order::extensionChildren()->get()->filter(function (Order $child) {
                $charge = ExtensionTransactionService::chargeForChild($child);

                return $charge && $charge->trashed();
            });
        }

        $this->info('Extension charges scanned: ' . $charges->count());
        $this->info('Phantom charges (child order deleted/missing): ' . $phantoms->count());
        $this->info('Orphan child orders (charge deleted): ' . $orphanChildren->count());
        $this->newLine();

        $repaired = 0;
        $skippedPaid = 0;

        foreach ($phantoms as $charge) {
            $child = $charge->child_order_id ? Order::withTrashed()->find($charge->child_order_id) : null;
            $state = ExtensionTransactionService::paymentState($charge, $child);

            $this->line(sprintf(
                'PHANTOM CHARGE %s | parent_order_id=%s | child=%s | status=%s | payment_state=%s | total=$%s',
                $charge->unique_id,
                $charge->parent_order_id,
                $child?->order_number ?? 'MISSING',
                $charge->status?->value,
                $state,
                number_format($charge->amount + $charge->tax_amount, 2),
            ));

            if (!$fix) {
                continue;
            }

            if ($state === ExtensionTransactionService::STATE_PAID_UNRESOLVED) {
                $this->warn("  → SKIPPED: paid with no Kabba refund/void. Delete it from the parent order's Billing Engine row, which captures the administrative disposition.");
                $skippedPaid++;
                continue;
            }

            ExtensionTransactionService::delete(
                $child,
                $charge,
                ExtensionTransactionService::ENTRY_BILLING_ROW,
                $actor,
            );
            $this->info('  → repaired (charge soft-deleted, parent history written).');
            $repaired++;
        }

        foreach ($orphanChildren as $child) {
            $charge = ExtensionTransactionService::chargeForChild($child);
            $state  = ExtensionTransactionService::paymentState($charge, $child);

            $this->line(sprintf(
                'ORPHAN CHILD %s (id %d) | charge=%s (deleted) | payment_state=%s',
                $child->order_number,
                $child->id,
                $charge?->unique_id,
                $state,
            ));

            if (!$fix) {
                continue;
            }

            if ($state === ExtensionTransactionService::STATE_PAID_UNRESOLVED) {
                $this->warn('  → SKIPPED: paid with no Kabba refund/void. Delete it from the order page, which captures the administrative disposition.');
                $skippedPaid++;
                continue;
            }

            ExtensionTransactionService::delete(
                $child,
                $charge,
                ExtensionTransactionService::ENTRY_CHILD_ORDER,
                $actor,
            );
            $this->info('  → repaired (child order soft-deleted, parent history written).');
            $repaired++;
        }

        $this->newLine();
        if ($fix) {
            $this->info("Repaired: {$repaired} | Skipped (paid, needs disposition): {$skippedPaid}");
        } else {
            $this->comment('Report-only run. Re-run with --fix --user=<id> to repair the listed records.');
        }

        return self::SUCCESS;
    }
}
