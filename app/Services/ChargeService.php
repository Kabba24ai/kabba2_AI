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
     * Shared New Fuel Charge modal (Dashboard V2) — the ONE canonical path
     * for creating a MANUAL fuel/damage charge from any admin surface
     * (Dashboard card, Fuel Workspace, CRM customer page). Consolidates the
     * two previously-duplicated implementations (Dashboard
     * FuelChargeStoreController and CRM ChargeStoreController each carried
     * their own copy of the CustomerAccount write + BillingEngine bridge,
     * both hardcoding orderId: null).
     *
     * Behavior is the audited union of those paths, unchanged:
     *   1. Legacy CustomerAccount 'charge' row (alert status 'pending') in
     *      its own committed transaction, plus updateCreditBalance() and a
     *      customer note — legacy data is durable before the bridge runs.
     *   2. BillingEngine bridge with the canonical ChargeTaxCalculator
     *      split and a per-record idempotency key; bridge failure is logged
     *      and never breaks the already-committed legacy write.
     *
     * New (and the reason this exists): optional $orderId links the charge
     * to an order — customer/order integrity is the CALLER's contract (the
     * controller derives/validates customer from the order). Phase 1
     * deliberately never sets order_product_id here: OrderProduct-linked
     * charges are created only by the checklist path
     * (createFromOrderProduct above), and a manual CA row with an
     * order_product_id would vanish from the canonical alert queue
     * (ChargeAlertQueue's CRM branch requires order_product_id IS NULL) —
     * exactly the duplicate/conflict class Phase 2 owns.
     *
     * @param  string  $type  'fuel' | 'damage'
     * @param  string  $sourceContext  short slug for metadata/idempotency, e.g. 'dashboard', 'fuel_workspace', 'crm'
     */
    public static function createManualCharge(
        int $customerId,
        string $type,
        float $amount,
        ?string $salesTaxType,
        ?string $notes,
        int $responsibleUserId,
        ?int $orderId = null,
        string $sourceContext = 'dashboard',
        ?float $storeCreditDiscount = null,
        ?string $discountIdempotencyKey = null,
    ): CustomerAccount {
        if ($salesTaxType !== null && !ChargeTaxCalculator::isValidTreatment($salesTaxType)) {
            throw new \InvalidArgumentException("Invalid sales tax treatment '{$salesTaxType}'.");
        }

        $user = User::findOrFail($responsibleUserId);

        $reason     = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';

        $applyDiscount = $storeCreditDiscount !== null && $storeCreditDiscount > 0;
        if ($applyDiscount && $discountIdempotencyKey === null) {
            $discountIdempotencyKey = "manual_{$type}_scd:" . (string) \Illuminate\Support\Str::uuid();
        }

        // Idempotency for discounted retries: if the discount was already
        // recorded, the charge already exists — return it, don't recreate.
        if ($applyDiscount && $discountIdempotencyKey !== null) {
            $existing = app(\App\Services\Discounts\DiscountApplicationService::class)->existingCreationDiscount($discountIdempotencyKey);
            if ($existing) {
                return CustomerAccount::findOrFail($existing->target_id);
            }
        }

        // ── Legacy write (committed first, same as both prior controllers) ──
        // A PRE-TAX Store Credit discount (Phase 1) is applied INSIDE this
        // transaction, BEFORE updateCreditBalance books A/R, so the redemption,
        // the discount record and the charge are one atomic unit.
        $record = DB::transaction(function () use ($customerId, $orderId, $amount, $reason, $alertField, $salesTaxType, $notes, $user, $type, $applyDiscount, $storeCreditDiscount, $discountIdempotencyKey) {
            $effectiveAmount = round($amount, 2);
            $effectiveTreatment = $salesTaxType ?? 'free';
            $pendingDiscount = null;

            if ($applyDiscount) {
                $cd = app(\App\Services\Discounts\DiscountApplicationService::class)->computeChargeDiscount(
                    $customerId, $amount, $salesTaxType, (float) $storeCreditDiscount,
                    $discountIdempotencyKey, $user->id, $type,
                );
                $effectiveAmount = $cd['effective_amount'];
                $effectiveTreatment = $cd['effective_treatment'];
                $pendingDiscount = $cd;
            }

            $record                          = new CustomerAccount();
            $record->customer_id             = $customerId;
            $record->order_id                = $orderId;
            $record->amount                  = $effectiveAmount;
            $record->reason                  = $reason;
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $notes;
            $record->date                    = now();
            $record->sales_tax_type          = $effectiveTreatment;
            $record->sales_tax               = 0;
            $record->type                    = 'charge';
            $record->$alertField             = 'pending';
            $record->save();

            CustomHelper::updateCreditBalance($record);

            if ($pendingDiscount !== null) {
                app(\App\Services\Discounts\DiscountApplicationService::class)->recordCreationDiscount(
                    $pendingDiscount['result'],
                    $pendingDiscount['redemption_id'],
                    $type === 'fuel'
                        ? \App\Enums\Discounts\DiscountTargetType::FuelCharge
                        : \App\Enums\Discounts\DiscountTargetType::DamageCharge,
                    (int) $record->id,
                    $customerId,
                    $discountIdempotencyKey,
                    $user->id,
                    (float) $storeCreditDiscount,
                    "Store Credit discount — {$type}",
                    'admin_' . $type,
                );
            }

            $description = "{$reason} added.";
            $description .= ' Amount: $' . number_format((float) $record->amount, 2) . '.';
            if ($record->responsible_person_name) {
                $description .= " Responsible person: {$record->responsible_person_name}.";
            }
            if (filled($notes)) {
                $description .= " Notes: {$notes}";
            }

            if ($record->customer) {
                $record->customer->notes()->create([
                    'customer_account_id' => $record->id,
                    'description'         => $description,
                    'created_by'          => auth()->id(),
                ]);
            }

            return $record;
        });

        // ── Billing Engine bridge (post-commit, failure never surfaces) ────
        // updateCreditBalance() set $record->sales_tax to the actual rate.
        $resolved = ChargeTaxCalculator::calculate((float) $record->amount, $record->sales_tax_type, (float) $record->sales_tax);

        $billingType = $type === 'fuel'
            ? \App\Enums\Billing\BillingChargeType::Fuel->value
            : \App\Enums\Billing\BillingChargeType::Damage->value;
        $sourceModule = $type === 'fuel'
            ? \App\Enums\Billing\BillingSourceModule::AdminFuelCharge->value
            : \App\Enums\Billing\BillingSourceModule::AdminDamageCharge->value;
        $sourceEvent = $type === 'fuel'
            ? \App\Enums\Billing\BillingSourceEvent::AdminFuelChargeCreated->value
            : \App\Enums\Billing\BillingSourceEvent::AdminDamageChargeCreated->value;

        try {
            BillingEngine::charge(new \App\Http\DataObjects\BillingChargeRequest(
                type:                $billingType,
                orderId:             $orderId,
                customerId:          (int) $record->customer_id,
                amount:              $resolved['base_amount'],
                taxType:             $record->sales_tax_type,
                responsiblePersonId: $user->id,
                notes:               $record->notes,
                sourceModule:        $sourceModule,
                sourceEvent:         $sourceEvent,
                sourceReferenceType: 'CustomerAccount',
                sourceReferenceId:   $record->id,
                metadata:            [
                    'creation_path'              => 'ChargeService::createManualCharge',
                    'source_context'             => $sourceContext,
                    'legacy_customer_account_id' => $record->id,
                    'sales_tax_type'             => $record->sales_tax_type,
                ],
                idempotencyKey:      "manual_{$type}_charge:{$record->id}",
                customerAccountId:   $record->id,
                taxAmount:           $resolved['tax_amount'],
            ));
        } catch (\Throwable $e) {
            Log::channel('billing_engine')->error(
                'BillingEngine bridge failed | path=ChargeService::createManualCharge '
                . "| context={$sourceContext} | customer_account_id={$record->id} "
                . "| customer_id={$record->customer_id} | amount={$record->amount} "
                . '| error=' . $e->getMessage()
            );
        }

        return $record;
    }

    /**
     * ST-2a — canonical creation for a SERVICE TICKET settlement charge.
     * Replaces the settlement's former raw order_extra_charges write with
     * the real Billing Engine path: a CustomerAccount ledger row + a
     * BillingChargeType::ServiceTicket charge, so the charge is a first-
     * class billing object (idempotent, refundable, payable through the
     * shared payment surfaces via its CustomerAccount).
     *
     * Differs from createManualCharge deliberately:
     *   - reason 'Service Repair'; sets NO fuel/damage alert flag (a service
     *     charge is not an operational alert and must never enter those
     *     queues);
     *   - TAX-FREE (approved decision — service charges are untaxed today;
     *     per-line taxable is a separate future decision);
     *   - the bridge exception is NOT swallowed — the caller (Settlement
     *     StoreController) runs this inside its own transaction and requires
     *     the BillingCharge, so a bridge failure must roll the settlement
     *     back rather than leave a charge-less settlement behind.
     *
     * Returns the BillingCharge (the settlement links it directly). Runs
     * inside the caller's transaction — no inner transaction here.
     *
     * @param  string  $idempotencyKey       e.g. "service_settlement:{id}"
     * @param  string  $sourceReferenceType  e.g. 'ServiceTicketSettlement'
     */
    public static function createServiceCharge(
        int $customerId,
        float $amount,
        ?int $orderId,
        int $responsibleUserId,
        string $notes,
        string $idempotencyKey,
        string $sourceReferenceType,
        int $sourceReferenceId,
    ): BillingCharge {
        $user = User::findOrFail($responsibleUserId);

        $record                          = new CustomerAccount();
        $record->customer_id             = $customerId;
        $record->order_id                = $orderId;
        $record->amount                  = $amount;
        $record->reason                  = 'Service Repair';
        $record->responsible_person_id   = $user->id;
        $record->responsible_person_name = $user->full_name;
        $record->notes                   = $notes;
        $record->date                    = now();
        $record->sales_tax_type          = 'free';
        $record->sales_tax               = 0;
        $record->type                    = 'charge';
        // Deliberately NO fuel_alert_status / damage_alert_status — a service
        // charge is not an operational alert.
        $record->save();

        CustomHelper::updateCreditBalance($record);

        if ($record->customer) {
            $description = 'Service Repair charge added. Amount: $' . number_format((float) $record->amount, 2) . '.';
            $description .= " Responsible person: {$record->responsible_person_name}.";
            if (filled($notes)) {
                $description .= " Notes: {$notes}";
            }
            $record->customer->notes()->create([
                'customer_account_id' => $record->id,
                'description'         => $description,
                'created_by'          => auth()->id(),
            ]);
        }

        // Tax-free: base = amount, tax = 0. Bridge failure propagates.
        return BillingEngine::charge(new \App\Http\DataObjects\BillingChargeRequest(
            type:                \App\Enums\Billing\BillingChargeType::ServiceTicket->value,
            orderId:             $orderId,
            customerId:          (int) $record->customer_id,
            amount:              $amount,
            taxType:             'free',
            responsiblePersonId: $user->id,
            notes:               $notes,
            sourceModule:        \App\Enums\Billing\BillingSourceModule::ServiceModule->value,
            sourceEvent:         \App\Enums\Billing\BillingSourceEvent::ServiceTicketChargeCreated->value,
            sourceReferenceType: $sourceReferenceType,
            sourceReferenceId:   $sourceReferenceId,
            metadata:            [
                'creation_path'              => 'ChargeService::createServiceCharge',
                'legacy_customer_account_id' => $record->id,
            ],
            idempotencyKey:      $idempotencyKey,
            customerAccountId:   $record->id,
            taxAmount:           0.0,
        ));
    }

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
        // Store Credit is NO LONGER a tender — it is a pre-tax discount applied
        // via the discount engine. Reject it here as defense-in-depth (this is
        // the one raw payment write site with no FormRequest of its own), so no
        // service path can ever write a Store Credit payment row.
        if (strcasecmp(trim($paymentType), 'StoreCredit') === 0) {
            throw new \App\Services\Discounts\DiscountException(
                'Store Credit is a discount, not a payment. Apply it through the discount engine, not recordPayment().'
            );
        }

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
