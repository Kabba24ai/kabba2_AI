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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargeService
{
    /**
     * Create a CustomerAccount charge record from a checklist-originated OrderProduct charge.
     * Idempotent — will not create a duplicate if one already exists for the same OP + type.
     *
     * @param  OrderProduct  $orderProduct  Must have order() and order.customer loaded or loadable.
     * @param  string        $type          'fuel' | 'damage'
     * @param  int|null      $responsibleUserId
     * @return CustomerAccount|null  Returns null if the charge amount is zero or a record already exists.
     */
    public static function createFromOrderProduct(
        OrderProduct $orderProduct,
        string $type,
        ?int $responsibleUserId = null
    ): ?CustomerAccount {
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

        // Duplicate guard — one pending/active CA charge per OP + type
        $exists = CustomerAccount::where('order_product_id', $orderProduct->id)
            ->where('reason', $reason)
            ->where('type', 'charge')
            ->whereIn($alertField, ['pending', 'completed'])
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
        $record->sales_tax_type          = 'free';
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
     * @param  string        $paymentType  'Cash'|'Cheque'|'CreditCard'|'BankTransfer'
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

        // Mark the OrderProduct status as completed
        $orderProduct->$statusField = OrderProductChargeStatus::Completed->value;
        $orderProduct->save();

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
        $order       = $orderProduct->order ?? $orderProduct->load('order')->order;
        $reason      = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $alertField  = $type === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';
        $statusField = $type === 'fuel' ? 'fuel_charge_status' : 'damage_status';
        $user        = User::find($resolvedByUserId);

        // Mark OP status
        $orderProduct->$statusField = OrderProductChargeStatus::Resolved->value;
        $orderProduct->save();

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
        $statusField = $type === 'fuel' ? 'fuel_charge_status' : 'damage_status';

        $orderProduct->$statusField = OrderProductChargeStatus::Uncollectible->value;
        $orderProduct->save();

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
