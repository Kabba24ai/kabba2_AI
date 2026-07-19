<?php

namespace App\Services;

use App\Enums\Orders\OrderProductChargeStatus;
use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Orders\OrderProduct;
use App\Models\Iam\Personnel\User;
use App\Events\Admin\Orders\OrderExtraChargeEvent;
use App\Services\AlertLifecycleService;
use App\Services\ChargeTaxCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargeService
{
    /**
     * Create a CustomerAccount charge record from a checklist-originated OrderProduct charge.
     * Idempotent — will not create a duplicate if one already exists for the same OP + type
     * within the same rental cycle (see $cycleStartedAt).
     *
     * @param  OrderProduct  $orderProduct  Must have order() and order.customer loaded or loadable.
     * @param  string        $type          'fuel' | 'damage'
     * @param  int|null      $responsibleUserId
     * @param  \Illuminate\Support\Carbon|\DateTimeInterface|null  $cycleStartedAt
     *         When provided, only an existing charge created at or after this timestamp counts
     *         as a duplicate for the current rental cycle — a charge from an earlier cycle on
     *         the same OrderProduct (delivery/return reuses the same row) no longer blocks a
     *         legitimate new charge. Pass the current cycle's delivery timestamp (e.g. the
     *         minimum created_at of the order product's currently-active checklist question
     *         rows, which are recreated on every save-delivery call). Null preserves the
     *         previous, cycle-unaware behavior. See CORRECTION_PHASE1_PLAN.md Issue #1.
     * @param  ?string       $salesTaxType  'add' | 'free' | 'reverse'. Sales Tax Architecture
     *         Correction: previously hardcoded 'free' unconditionally. Reconsidered after
     *         review: defaulting a NULL (app-not-yet-updated) submission to 'add' would be a
     *         silent, unilateral tax-policy reversal for every mobile fuel charge — there was
     *         never an employee-expressed intent to tax these (unlike the CRM/Dashboard fixes,
     *         which restored a choice the employee actually made and the system discarded).
     *         The mission's own caution against silently choosing an unstated business-policy
     *         answer applies here just as much as to damage subtypes. Null therefore preserves
     *         the exact current, already-in-production behavior ('free') rather than changing
     *         it — 'add' becoming the default is an explicit business decision to make
     *         separately, once someone can actually update the mobile app to expose a choice
     *         and the decision can be rolled out deliberately rather than silently. An invalid
     *         non-null value is still rejected rather than defaulted.
     * @return CustomerAccount|null  Returns null if the charge amount is zero or a record already exists.
     */
    public static function createFromOrderProduct(
        OrderProduct $orderProduct,
        string $type,
        ?int $responsibleUserId = null,
        $cycleStartedAt = null,
        ?string $salesTaxType = null
    ): ?CustomerAccount {
        if ($salesTaxType !== null && !ChargeTaxCalculator::isValidTreatment($salesTaxType)) {
            throw new \InvalidArgumentException("Invalid sales tax treatment '{$salesTaxType}'.");
        }
        $salesTaxType ??= ChargeTaxCalculator::TREATMENT_FREE;
        $amount = $type === 'fuel'
            ? (float) ($orderProduct->fuel_total_charge ?? 0)
            : (float) ($orderProduct->damage_charge ?? 0);

        if ($amount <= 0) {
            return null;
        }

        $order = $orderProduct->order ?? $orderProduct->load('order')->order;

        if (!$order) {
            Log::warning("ChargeService::createFromOrderProduct — no order found for OrderProduct #{$orderProduct->id}");
            return null;
        }

        $reason      = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField  = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';

        // Duplicate guard — one pending/active CA charge per OP + type + rental cycle
        $exists = CustomerAccount::where('order_product_id', $orderProduct->id)
            ->where('reason', $reason)
            ->where('type', 'charge')
            ->whereIn($alertField, ['pending', 'completed'])
            ->when($cycleStartedAt !== null, fn ($q) => $q->where('created_at', '>=', $cycleStartedAt))
            ->exists();

        if ($exists) {
            return null;
        }

        $user = $responsibleUserId ? User::find($responsibleUserId) : null;

        $record                          = new CustomerAccount();
        $record->customer_id             = $order->customer_id;
        $record->order_id                = $order->id;
        $record->order_product_id        = $orderProduct->id;
        $record->amount                  = $amount;
        $record->reason                  = $reason;
        $record->responsible_person_id   = $user?->id;
        $record->responsible_person_name = $user?->full_name;
        $record->date                    = now();
        $record->sales_tax_type          = $salesTaxType;
        $record->sales_tax               = 0;
        $record->type                    = 'charge';
        $record->$alertField             = 'pending';
        $record->save();

        CustomHelper::updateCreditBalance($record);

        return $record;
    }

    /**
     * Record payment collected against an OrderProduct-based charge.
     * Creates a CustomerAccount payment entry, marks the OP status as completed,
     * and marks any linked CA charge record as completed.
     *
     * @param  OrderProduct  $orderProduct
     * @param  string        $type         'fuel' | 'damage'
     * @param  float         $amount
     * @param  string        $paymentType  One of App\Enums\Customers\PaymentMethod::canonical()
     * @param  int           $responsibleUserId
     * @param  array         $extra        Optional gateway fields (auth_code, transaction_id, etc.)
     * @return CustomerAccount  The payment CustomerAccount record.
     */
    public static function recordPayment(
        OrderProduct $orderProduct,
        string $type,
        float $amount,
        string $paymentType,
        int $responsibleUserId,
        array $extra = []
    ): CustomerAccount {
        $order  = $orderProduct->order ?? $orderProduct->load('order')->order;
        $reason = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField  = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';
        $statusField = $type === 'fuel' ? 'fuel_charge_status' : 'damage_status';
        $user   = User::findOrFail($responsibleUserId);

        $payment                          = new CustomerAccount();
        $payment->customer_id             = $order->customer_id;
        $payment->order_id                = $order->id;
        $payment->order_product_id        = $orderProduct->id;
        $payment->amount                  = $amount;
        $payment->reason                  = 'Payment — ' . $reason;
        $payment->responsible_person_id   = $user->id;
        $payment->responsible_person_name = $user->full_name;
        $payment->date                    = now();
        $payment->payment_type            = $paymentType;
        $payment->sales_tax               = 0;
        $payment->type                    = 'payment';
        $payment->payment_number_id       = $extra['transaction_id'] ?? $extra['cheque_number'] ?? null;
        $payment->auth_code               = $extra['auth_code'] ?? null;
        $payment->customer_profile_id     = $extra['customer_profile_id'] ?? null;
        $payment->payment_profile_id      = $extra['payment_profile_id'] ?? null;
        $payment->save();

        LedgerBalanceService::applyTransaction($payment);

        // Mark the OrderProduct completed under a row lock and record the
        // lifecycle transition atomically (caller runs inside a transaction).
        AlertLifecycleService::transitionOrderProduct((int) $orderProduct->id, $type, 'completed', $responsibleUserId);

        // Mark any linked CA charge record as completed so it disappears from alerts
        CustomerAccount::where('order_product_id', $orderProduct->id)
            ->where('type', 'charge')
            ->where('reason', $reason)
            ->where($alertField, 'pending')
            ->update([$alertField => 'completed']);

        return $payment;
    }

    /**
     * Mark an OrderProduct charge as resolved (waived/written off).
     * Updates the OP status, marks the linked CA charge as resolved,
     * and creates a reversal entry to zero out the balance impact.
     *
     * @param  OrderProduct  $orderProduct
     * @param  string        $type         'fuel' | 'damage'
     * @param  string        $resolutionNote
     * @param  int           $resolvedByUserId
     */
    public static function markResolved(
        OrderProduct $orderProduct,
        string $type,
        string $resolutionNote,
        int $resolvedByUserId
    ): void {
        $reason      = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField  = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';
        $user        = User::find($resolvedByUserId);

        // Lock the OrderProduct, mark it resolved, and record the lifecycle
        // transition atomically. Skip safely if it already left the queue.
        $orderProduct = AlertLifecycleService::transitionOrderProduct((int) $orderProduct->id, $type, 'resolved', $resolvedByUserId);
        if (! $orderProduct) {
            return;
        }
        $order = $orderProduct->order ?? $orderProduct->load('order')->order;

        // Mark linked CA charge records as resolved and create reversal entries
        $chargeRecords = CustomerAccount::where('order_product_id', $orderProduct->id)
            ->where('type', 'charge')
            ->where('reason', $reason)
            ->where($alertField, 'pending')
            ->get();

        foreach ($chargeRecords as $chargeRecord) {
            $chargeRecord->$alertField = 'resolved';
            $chargeRecord->save();

            // Reversal to zero the balance impact
            $reversal                          = new CustomerAccount();
            $reversal->customer_id             = $order->customer_id;
            $reversal->order_id                = $order->id;
            $reversal->order_product_id        = $orderProduct->id;
            $reversal->amount                  = $chargeRecord->amount;
            $reversal->reason                  = 'Resolved — ' . $reason;
            $reversal->notes                   = $resolutionNote;
            $reversal->responsible_person_id   = $user?->id;
            $reversal->responsible_person_name = $user?->full_name;
            $reversal->date                    = now();
            $reversal->sales_tax               = 0;
            $reversal->sales_tax_type          = 'free';
            $reversal->type                    = 'discount';
            $reversal->save();

            CustomHelper::updateCreditBalance($reversal);
        }

        // Sync any mobile-originated BillingCharges (customer_account_id IS NULL) that are
        // linked to this OrderProduct — these have no CA record so the loop above misses them.
        $bcType = $type === 'fuel' ? 'fuel' : 'damage';
        BillingCharge::where('order_product_id', $orderProduct->id)
            ->whereNull('customer_account_id')
            ->where('billing_charge_type', $bcType)
            ->where('status', 'pending')
            ->each(fn ($bc) => BillingEngine::markResolved($bc, $resolutionNote, $resolvedByUserId));
    }

    /**
     * Mark an OrderProduct charge as uncollectible.
     * Updates the OP status and marks any linked CA charge record accordingly.
     *
     * @param  OrderProduct  $orderProduct
     * @param  string        $type         'fuel' | 'damage'
     * @param  int           $markedByUserId
     */
    public static function markUncollectible(
        OrderProduct $orderProduct,
        string $type,
        int $markedByUserId
    ): void {
        $reason      = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField  = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';

        // Lock the OrderProduct, mark it uncollectible, and record the lifecycle
        // transition atomically. Skip safely if it already left the queue.
        $orderProduct = AlertLifecycleService::transitionOrderProduct((int) $orderProduct->id, $type, 'uncollectible', $markedByUserId);
        if (! $orderProduct) {
            return;
        }

        CustomerAccount::where('order_product_id', $orderProduct->id)
            ->where('type', 'charge')
            ->where('reason', $reason)
            ->where($alertField, 'pending')
            ->update([$alertField => 'uncollectible']);

        // Sync any mobile-originated BillingCharges (customer_account_id IS NULL).
        $bcType = $type === 'fuel' ? 'fuel' : 'damage';
        BillingCharge::where('order_product_id', $orderProduct->id)
            ->whereNull('customer_account_id')
            ->where('billing_charge_type', $bcType)
            ->where('status', 'pending')
            ->each(fn ($bc) => BillingEngine::markUncollectible($bc, $markedByUserId));
    }
}
