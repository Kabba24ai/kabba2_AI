<?php

namespace App\Services\Orders;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderProduct;
use App\Services\FunnelLifecycleService;
use Illuminate\Support\Facades\DB;

/**
 * Automatic operational closure for orders whose money is gone.
 *
 * Business rule: a fully refunded or voided order must no longer be treated
 * as an UPCOMING delivery — but equipment that has already entered the field
 * must keep being tracked through return regardless of any refund. A refund
 * after delivery is financial activity only; it does not prove the equipment
 * came back.
 *
 * This service is the single place that rule lives. It is invoked from the
 * two canonical financial-completion points:
 *   - full refund:  CloseSchedulesOnFullRefundListener (RefundInitiateEvent,
 *     fired by RefundPaymentController AFTER PaymentAllocationService::
 *     syncRefundOperationOutcome() has finalized the refund outcome)
 *   - void:         VoidPaymentController, after the gateway void succeeded
 *     and the payment row was stamped Voided (both success exits)
 *
 * Eligibility is always re-derived from canonical payment state
 * (OrderPaymentSummary / OrderPayment rows), never from the triggering
 * request — so a failed refund can never close anything, and re-processing
 * the same event is a no-op.
 *
 * The closure itself mirrors the manual admin "Close as Completed" action
 * (UpdateProductScheduleController's delivery branch) EXACTLY — same status
 * value, same is_delivered/is_returned/pickup_status writes, same equipment
 * release to Maintenance with an EquipmentStatusLog transition. No second
 * meaning of "completed" is introduced. The only additions are System-
 * attributed order history (never a fake manual entry) and stopping
 * before-event delivery-reminder funnels (a refunded order must not keep
 * receiving "your delivery is coming" messages) — the same funnel stop the
 * 'Completed' transition already performs via OrderProduct::boot().
 *
 * Scope: per order-product row (the schedule's own granularity). Rows with
 * ANY delivery evidence are left untouched, so a partially delivered order
 * keeps its delivered rows operationally visible while its undelivered rows
 * close. See OrderProduct::hasBeenDelivered() for the evidence definition.
 */
final class RefundedOrderScheduleCloser
{
    public const TRIGGER_REFUND = 'full_refund';

    public const TRIGGER_VOID = 'void';

    /**
     * Called after every completed refund operation. Closes undelivered
     * schedules only when the refund is OPERATIONALLY COMPLETE (see
     * isOperationallyCompleteRefund below) — ordinary partial refunds
     * never qualify.
     *
     * @return int number of order-product schedule rows closed
     */
    public static function afterFullRefund(Order $order, OrderPayment $refund, ?User $initiator = null): int
    {
        $summary = OrderPaymentSummary::for($order);

        if (! self::isOperationallyCompleteRefund($order, $summary)) {
            return 0;
        }

        return self::closeUndeliveredSchedules(
            order: $order,
            trigger: self::TRIGGER_REFUND,
            financialRecord: $refund,
            initiator: $initiator,
            // Audit precision: the history entry must not claim "fully
            // refunded" when the business intentionally retained the fee.
            refundLessFee: $summary->refundStatus !== OrderPaymentSummary::REFUND_FULL,
        );
    }

    /**
     * THE operational-completion predicate for refunds (approved business
     * rule): the order's money is gone for scheduling purposes when either
     *
     *  1. it is FULLY refunded (canonical OrderPaymentSummary::REFUND_FULL,
     *     unchanged), or
     *  2. it was refunded "Full Amount Less Card Processing Fee" — the
     *     complete settled balance came back to the customer EXCEPT fees
     *     the business intentionally retained. Financially that is (and
     *     must remain) REFUND_PARTIAL — reporting, receipts, and payment
     *     history keep distinguishing the two — but the order has been
     *     economically concluded, so Schedule/Dispatch treat it exactly
     *     like a full refund.
     *
     * The fee-retained branch is deliberately strict: some money must have
     * actually been refunded, some fee must actually have been retained
     * (Allocated allocations only — PaymentAllocationService::
     * totalFeesRetained), and net-refunded + retained fees must cover the
     * settled total (same half-cent tolerance REFUND_FULL uses). An
     * ordinary partial refund (no fee retained) or a partial fee-retained
     * refund (money still legitimately held beyond the fee) never passes.
     *
     * OPERATIONAL USE ONLY — never use this predicate for financial
     * classification, reporting, or display; that is refundStatus's job.
     */
    public static function isOperationallyCompleteRefund(Order $order, ?OrderPaymentSummary $summary = null): bool
    {
        $summary ??= OrderPaymentSummary::for($order);

        if ($summary->refundStatus === OrderPaymentSummary::REFUND_FULL) {
            return true;
        }

        if ($summary->refundStatus !== OrderPaymentSummary::REFUND_PARTIAL) {
            return false; // nothing refunded — nothing to conclude
        }

        $feesRetained = PaymentAllocationService::totalFeesRetained($order);

        if ($feesRetained <= 0.0) {
            return false; // ordinary partial refund — money is still held
        }

        return ($summary->totalRefunded + $feesRetained + 0.005) >= $summary->totalSettledPayments;
    }

    /**
     * Called after a successful gateway void — but ONLY when the employee
     * EXPLICITLY chose "Void payment & cancel rental" in the void modal.
     *
     * A void by itself is payment-lifecycle activity, not an order
     * cancellation: the common void-then-recharge workflow (fix a mistaken
     * charge, rental proceeds) must leave the schedule untouched, so
     * cancellation intent is never inferred from payment state (zero
     * balance, Voided status, etc.). The caller (VoidPaymentController)
     * gates this on the explicit cancel_order flag; delivered-equipment
     * protection and idempotency still apply unconditionally here.
     *
     * @return int number of order-product schedule rows closed
     */
    public static function afterVoid(Order $order, OrderPayment $voidedPayment, ?User $initiator = null): int
    {
        return self::closeUndeliveredSchedules(
            order: $order,
            trigger: self::TRIGGER_VOID,
            financialRecord: $voidedPayment,
            initiator: $initiator,
        );
    }

    /**
     * Transactional, idempotent core. Skips rows already administratively
     * closed and rows with any delivery evidence; writes one System-
     * attributed history entry only when at least one row actually closed.
     */
    private static function closeUndeliveredSchedules(
        Order $order,
        string $trigger,
        OrderPayment $financialRecord,
        ?User $initiator,
        bool $refundLessFee = false,
    ): int {
        return DB::transaction(function () use ($order, $trigger, $financialRecord, $initiator, $refundLessFee) {
            $rows = $order->products()
                ->with('equipment')
                ->where('product_data->product_type', 'Rental')
                ->get();

            $funnelReason = $trigger === self::TRIGGER_VOID
                ? FunnelLifecycleService::REASON_ORDER_VOIDED
                : FunnelLifecycleService::REASON_ORDER_FULLY_REFUNDED;

            /** @var array<int, OrderProduct> $closed */
            $closed = [];

            foreach ($rows as $orderProduct) {
                // Already administratively closed (manually or by a prior
                // run) — nothing to do. Checked BEFORE hasBeenDelivered()
                // because Close as Completed itself sets is_delivered=true.
                if ($orderProduct->delivery_status === 'Close as Completed') {
                    continue;
                }

                // SAFETY RULE: any evidence the equipment entered the field
                // (or is on a truck at the customer's site) means this row
                // must stay operationally visible through return.
                if ($orderProduct->hasBeenDelivered()) {
                    continue;
                }

                // Mirror of UpdateProductScheduleController's manual
                // 'Close as Completed' delivery branch — identical writes.
                $orderProduct->delivery_status = 'Close as Completed';
                $orderProduct->is_delivered = true;
                $orderProduct->is_returned = true;
                $orderProduct->pickup_status = 'Completed';

                $equipment = $orderProduct->equipment;
                if ($equipment) {
                    $beforeStatus = $equipment->current_status?->value;
                    $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
                    $equipment->current_status_updated_by = $initiator?->id;
                    $equipment->current_status_changed_at = now();
                    $equipment->current_order_id = null;
                    $equipment->current_order_product_id = null;
                    $equipment->saveQuietly();
                    EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Maintenance->value, $initiator?->id);
                }

                $orderProduct->save();

                // A cancelled order must not keep sending "your delivery is
                // coming" reminders. Before-event steps only — idempotent
                // (FunnelLifecycleService dedups on existing log rows).
                FunnelLifecycleService::stopDeliveryReminderFunnels($orderProduct, $funnelReason);

                $closed[] = $orderProduct;
            }

            if ($closed === []) {
                return 0;
            }

            $productNames = collect($closed)->pluck('product_name')->filter()->implode(', ');

            $description = match (true) {
                $trigger === self::TRIGGER_VOID => 'Delivery schedule automatically closed as completed because the order was voided before delivery and the rental was explicitly cancelled.',
                $refundLessFee => 'Delivery schedule automatically closed as completed because the order was refunded in full (less the retained card processing fee) before delivery.',
                default => 'Delivery schedule automatically closed as completed because the order was fully refunded before delivery.',
            };

            if ($productNames !== '') {
                $description .= " ({$productNames})";
            }

            if ($initiator) {
                $description .= " Financial action by {$initiator->full_name}.";
            }

            $order->history()->create([
                'user_id' => null,
                'customer_id' => null,
                'order_payment_id' => $financialRecord->id,
                'action_date' => now(),
                'action_by' => OrderHistoryActionBy::System,
                'action' => OrderHistoryAction::ScheduleAutoClosed,
                'description' => $description,
                'extras' => json_encode([
                    'trigger' => $trigger,
                    'refund_less_processing_fee' => $refundLessFee,
                    'order_payment_id' => $financialRecord->id,
                    'initiated_by_user_id' => $initiator?->id,
                    'initiated_by_user_name' => $initiator?->full_name,
                    'closed_order_product_ids' => collect($closed)->pluck('id')->all(),
                ]),
            ]);

            return count($closed);
        });
    }
}
