<?php

namespace App\Services\Billing;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Services\AlertLifecycleService;
use Illuminate\Support\Facades\DB;

/**
 * Add Charge to Account — the ONE canonical writer that transfers an eligible
 * unpaid supplemental charge (fuel / damage / extension) to the customer's
 * credit account (Accounts Receivable).
 *
 * This is an A-R TRANSFER, not a receipt of money. After a successful
 * transfer:
 *   - the charge is closed on the order as BillingChargeStatus::Account
 *     ("On Account") — NOT 'paid', so it never enters the cash/card/revenue
 *     or sales-tax recognition streams (those key on status='paid');
 *   - the customer's credit-account balance reflects the outstanding amount
 *     exactly ONCE (see the idempotent-booking note below);
 *   - the charge leaves the ordinary fuel/damage collection workspace;
 *   - no cash/check/card/gateway payment record is created.
 *
 * IDEMPOTENT A-R BOOKING (the load-bearing correctness rule):
 *   Fuel/damage charges book the A-R debt at CREATION time (manual +
 *   checklist-fuel write a customer_accounts 'charge' row + updateCreditBalance
 *   immediately) — for those, billing_charges.customer_account_id is already
 *   set. Extension and mobile-checklist-damage charges do NOT book A-R at
 *   creation (customer_account_id is null). So the presence of
 *   customer_account_id is the exact signal: null ⇒ not yet in A-R, book it
 *   now; non-null ⇒ already in A-R, do NOT re-book (that would double the
 *   receivable). Either way the receivable ends up on the account exactly once.
 *
 * Concurrency/idempotency: transfer() runs in a single DB transaction and
 * SELECT … FOR UPDATE locks the charge, re-reads its status, and short-circuits
 * if it is already On Account — a double-click / retry / concurrent request can
 * never create a second A-R debit.
 *
 * Reversal is intentionally NOT part of v1 (approved: defer + document). The
 * credit-account ledger reverses today only by hard delete (CustomerAccount
 * DeleteController); an append-only reversal for these transfers is a separate
 * mission. Until then, a mistaken transfer must be corrected through the
 * existing account-adjustment tooling by an authorized user.
 */
class AddChargeToAccountService
{
    /** Charge types this feature supports. */
    public const SUPPORTED_TYPES = ['fuel', 'damage', 'extension'];

    /**
     * An active, eligible credit account = the same gate the rest of the app
     * uses (CustomHelper::getAvailableCredit): is_credit_account AND a positive
     * credit_limit. Note the app enforces NO credit-limit ceiling on account
     * orders today, so — matching a normal account order — this does not block
     * on balance vs limit either.
     */
    public static function customerIsEligible(?Customer $customer): bool
    {
        return $customer !== null
            && (int) ($customer->is_credit_account ?? 0) === 1
            && (float) ($customer->credit_limit ?? 0) > 0;
    }

    /** Supported charge type? */
    public static function typeIsSupported(?BillingCharge $charge): bool
    {
        return $charge !== null
            && in_array($charge->billing_charge_type?->value, self::SUPPORTED_TYPES, true);
    }

    /**
     * Canonical current outstanding balance of a charge. A BillingCharge has
     * no partial-collection model: it is fully outstanding (amount + tax) while
     * open (status pending), and 0 once closed. This mirrors the exact-total
     * rule asserted in PaymentStoreController::validateAmountAgainstBillingCharge.
     */
    public static function outstanding(BillingCharge $charge): float
    {
        return $charge->status?->isOpen()
            ? round((float) $charge->amount + (float) $charge->tax_amount, 2)
            : 0.0;
    }

    /**
     * Human-readable reason this charge cannot be transferred, or null when it
     * is eligible. Used to gate the UI and to build the confirmation modal; the
     * transfer() method independently re-checks everything under a row lock, so
     * this is advisory only — never the security control.
     */
    public static function eligibilityError(BillingCharge $charge): ?string
    {
        if (! self::typeIsSupported($charge)) {
            return 'Only fuel, damage, and extension charges can be added to account.';
        }
        if (! $charge->status?->isOpen()) {
            return $charge->status?->isOnAccount()
                ? "This charge is already on the customer's account."
                : 'This charge is already closed.';
        }
        if (! $charge->customer) {
            return 'This charge has no customer.';
        }
        if (! self::customerIsEligible($charge->customer)) {
            return 'This customer does not have an active credit account.';
        }
        if (self::outstanding($charge) <= 0) {
            return 'This charge has no outstanding balance to transfer.';
        }

        return null;
    }

    /**
     * Transfer the charge to the customer's credit account. Atomic + idempotent.
     *
     * @return array{status: 'transferred'|'already', charge: BillingCharge, ledger_row?: ?CustomerAccount, outstanding?: float, booked?: bool}
     *
     * @throws \DomainException with a user-safe message on a hard ineligibility.
     */
    public static function transfer(string $chargeUniqueId, int $actorUserId, ?string $note = null): array
    {
        $actor = User::findOrFail($actorUserId);

        return DB::transaction(function () use ($chargeUniqueId, $actor, $note) {
            /** @var BillingCharge $charge */
            $charge = BillingCharge::where('unique_id', $chargeUniqueId)->lockForUpdate()->firstOrFail();

            // ── Idempotent re-entry: already transferred ─────────────────────
            if ($charge->status?->isOnAccount()) {
                return ['status' => 'already', 'charge' => $charge];
            }

            // ── Revalidate every precondition under the lock ─────────────────
            if (! self::typeIsSupported($charge)) {
                throw new \DomainException('Only fuel, damage, and extension charges can be added to account.');
            }
            if (! $charge->status?->isOpen()) {
                throw new \DomainException('This charge is already closed and cannot be added to account.');
            }

            $customer = $charge->customer()->lockForUpdate()->first();
            if (! $customer) {
                throw new \DomainException('This charge has no customer.');
            }
            if (! self::customerIsEligible($customer)) {
                throw new \DomainException('This customer does not have an active credit account.');
            }

            $outstanding = self::outstanding($charge);
            if ($outstanding <= 0) {
                throw new \DomainException('This charge has no outstanding balance to transfer.');
            }

            $type = $charge->billing_charge_type->value; // fuel | damage | extension

            // ── Step 1: ensure the receivable is on the account EXACTLY ONCE ─
            $ledgerRow = $charge->legacyCustomerAccount;
            $booked    = false;
            if ($charge->customer_account_id === null) {
                $ledgerRow = self::bookReceivable($charge, $customer, $actor, $type, $outstanding);
                $charge->customer_account_id = $ledgerRow->id;
                $booked = true;
            }

            // ── Step 2: close the charge as On Account (never 'paid') ────────
            $charge->status = BillingChargeStatus::Account;
            $charge->responsible_person_id = $charge->responsible_person_id ?? $actor->id;
            $charge->notes = self::appendNote(
                $charge->notes,
                'Added to Account by ' . $actor->full_name . ' on ' . now()->format('M j, Y') . ($note ? ' — ' . $note : '')
            );
            $charge->save();

            // ── Step 3: leave the fuel/damage collection workspace ───────────
            self::removeFromWorkspace($charge, $type, (int) $actor->id);

            // ── Step 4: extension — reflect A-R on the child order's payment ─
            if ($type === 'extension') {
                self::markExtensionChildOnAccount($charge);
            }

            // ── Step 5: CRM audit trail on the ledger row ────────────────────
            self::writeAuditNote($charge, $ledgerRow, $customer, $actor, $outstanding, $booked, $note);

            return [
                'status'      => 'transferred',
                'charge'      => $charge,
                'ledger_row'  => $ledgerRow,
                'outstanding' => $outstanding,
                'booked'      => $booked,
            ];
        });
    }

    /**
     * Book the outstanding balance as a credit-account debit. The charge total
     * is already tax-inclusive, so the ledger row is written TAX-FREE (amount =
     * full outstanding) — updateCreditBalance must add exactly the amount owed,
     * never re-derive and re-add tax. Increases both the row's running balance
     * and customers.available_credit_balance by the outstanding amount.
     */
    private static function bookReceivable(
        BillingCharge $charge,
        Customer $customer,
        User $actor,
        string $type,
        float $outstanding
    ): CustomerAccount {
        $record                          = new CustomerAccount();
        $record->customer_id             = $customer->id;
        $record->order_id                = $charge->child_order_id ?? $charge->parent_order_id;
        $record->order_product_id        = $charge->order_product_id;
        $record->amount                  = $outstanding;
        $record->reason                  = self::ledgerReason($charge, $type);
        $record->responsible_person_id   = $actor->id;
        $record->responsible_person_name = $actor->full_name;
        $record->date                    = now();
        $record->sales_tax               = 0;
        $record->sales_tax_type          = 'free'; // amount is tax-inclusive — do not re-add tax
        $record->type                    = 'charge';
        $record->save();

        CustomHelper::updateCreditBalance($record);

        return $record;
    }

    /** Identifiable CRM ledger label, e.g. "Rental Extension — Order #3151-A". */
    private static function ledgerReason(BillingCharge $charge, string $type): string
    {
        $label = match ($type) {
            'fuel'      => 'Fuel Charge',
            'damage'    => 'Damage Charge',
            'extension' => 'Order Enhancement',
            default     => 'Charge',
        };

        $orderNumber = $charge->childOrder?->order_number ?? $charge->parentOrder?->order_number;

        return $orderNumber ? "{$label} — Order #{$orderNumber}" : $label;
    }

    /**
     * Remove the charge from the ordinary fuel/damage collection workspace by
     * transitioning its OrderProduct and/or linked CustomerAccount alert to the
     * 'account' operational state (excluded from ChargeAlertQueue). Extensions
     * never enter these queues. Transitions run through AlertLifecycleService so
     * they are row-locked and lifecycle-logged.
     */
    private static function removeFromWorkspace(BillingCharge $charge, string $type, int $actorId): void
    {
        if ($type !== 'fuel' && $type !== 'damage') {
            return;
        }

        // Checklist-originated charges are keyed by OrderProduct.
        if ($charge->order_product_id) {
            AlertLifecycleService::transitionOrderProduct((int) $charge->order_product_id, $type, 'account', $actorId);
        }

        // Manual + checklist-fuel charges carry a linked CustomerAccount alert.
        if ($charge->customer_account_id) {
            $alertField = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';
            $ca = CustomerAccount::whereKey($charge->customer_account_id)->first();
            if ($ca && $ca->$alertField === 'pending') {
                AlertLifecycleService::transitionCustomerAccount((int) $ca->id, $type, 'account', $actorId);
            }
        }
    }

    /**
     * An extension charge's obligation lives on its child order's payment row
     * (a pending COD placeholder). Flip it to Account/Account — the same marker
     * a normal on-account order uses — so the extension leaves POD reporting and
     * is treated as A-R. OrderPaymentStatus::Account is excluded from every
     * settled/cash/card total, so no receipt is recognized.
     */
    private static function markExtensionChildOnAccount(BillingCharge $charge): void
    {
        $childOrder = $charge->childOrder;
        if (! $childOrder) {
            return;
        }

        $payment = $childOrder->payments()->pending()->cod()->latest('id')->first();
        if ($payment) {
            $payment->status         = OrderPaymentStatus::Account;
            $payment->payment_method = OrderPaymentMethod::Account;
            $payment->save();
        }
    }

    /** Append to an existing free-text note without clobbering it. */
    private static function appendNote(?string $existing, string $addition): string
    {
        return filled($existing) ? trim($existing) . ' | ' . $addition : $addition;
    }

    /** Customer-facing CRM note linked to the ledger row (mirrors ChargeService). */
    private static function writeAuditNote(
        BillingCharge $charge,
        ?CustomerAccount $ledgerRow,
        Customer $customer,
        User $actor,
        float $outstanding,
        bool $booked,
        ?string $note
    ): void {
        $description = 'Charge added to account. '
            . 'Amount: $' . number_format($outstanding, 2) . '. '
            . 'Charge: ' . ($charge->billing_charge_type?->label() ?? 'Charge') . '. '
            . 'By: ' . $actor->full_name . '.';

        if (! $booked) {
            $description .= ' (Receivable was already on the account from charge creation — '
                . 'account balance unchanged; charge marked On Account and removed from collection.)';
        }

        if (filled($note)) {
            $description .= ' Notes: ' . $note;
        }

        $customer->notes()->create([
            'customer_account_id' => $ledgerRow?->id,
            'description'         => $description,
            'created_by'          => $actor->id,
        ]);
    }
}
