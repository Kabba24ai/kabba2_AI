<?php

namespace App\Services;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Orders\ExtensionDeletionReason;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * An extension child order (e.g. "2996-A") and its originating Rental
 * Extension BillingCharge are ONE transaction and share one lifecycle.
 * Every deletion entry point — the child-order Delete action and the
 * Billing Engine row Delete action — must go through delete() so neither
 * record can remain active without the other.
 *
 * Deletion is always the coordinated SOFT delete already used by Order,
 * OrderPayment, and BillingCharge; charge status is intentionally left
 * as-is (a paid charge stays 'paid' in the trashed row) so financial
 * reconciliation, refund investigation, and tax verification keep their
 * source data.
 */
class ExtensionTransactionService
{
    public const ENTRY_CHILD_ORDER = 'child_order';
    public const ENTRY_BILLING_ROW = 'billing_engine_row';

    public const STATE_UNPAID          = 'unpaid';
    public const STATE_PAID_SETTLED    = 'paid_settled';    // refunded/voided through Kabba
    public const STATE_PAID_UNRESOLVED = 'paid_unresolved'; // paid, no Kabba refund/void recorded

    /**
     * The canonical charge for an extension child order. withTrashed so a
     * counterpart deleted earlier can still be resolved (idempotency and
     * one-sided-history repair both depend on this).
     */
    public static function chargeForChild(Order $order): ?BillingCharge
    {
        return BillingCharge::withTrashed()
            ->where('billing_charge_type', BillingChargeType::Extension->value)
            ->where('child_order_id', $order->id)
            ->latest('id')
            ->first();
    }

    /**
     * Payment state of the transaction, driving the deletion gate:
     * unpaid and paid_settled may be deleted after plain confirmation;
     * paid_unresolved requires an administrative disposition.
     */
    public static function paymentState(?BillingCharge $charge, ?Order $child): string
    {
        $payments = $child
            ? $child->payments()->withTrashed()->get()
            : collect();

        $paid = ($charge && ($charge->isPaid() || $charge->paid_at !== null))
            || $payments->contains(fn ($p) => $p->status === OrderPaymentStatus::Paid);

        if (!$paid) {
            return self::STATE_UNPAID;
        }

        $settled = $payments->contains(fn ($p) => in_array($p->status, [
            OrderPaymentStatus::Refund,
            OrderPaymentStatus::PartialRefund,
            OrderPaymentStatus::Voided,
        ], true));

        return $settled ? self::STATE_PAID_SETTLED : self::STATE_PAID_UNRESOLVED;
    }

    public static function requiresDisposition(?BillingCharge $charge, ?Order $child): bool
    {
        return self::paymentState($charge, $child) === self::STATE_PAID_UNRESOLVED;
    }

    /** Normalize validated disposition input into the audit shape. */
    public static function buildDisposition(array $validated): array
    {
        $processedBy = User::findOrFail($validated['processed_by']);
        $reason      = ExtensionDeletionReason::from($validated['reason']);

        return [
            'processed_by_id'        => $processedBy->id,
            'processed_by_name'      => $processedBy->full_name,
            'processed_reason_code'  => $reason->value,
            'processed_reason_label' => $reason->label(),
            'processed_reason_other' => $validated['reason_other'] ?? null,
            'notes'                  => $validated['notes'] ?? null,
        ];
    }

    /**
     * Coordinated soft deletion of one extension transaction. Either side
     * may be null/trashed already — the surviving side is still removed, so
     * a missing counterpart never leaves the remaining record active.
     * Repeated calls are no-ops.
     *
     * The caller is responsible for the paid-state gate (requiresDisposition)
     * BEFORE calling; delete() itself only records whatever disposition it
     * is given.
     *
     * @return array{deleted: bool, already_deleted: bool}
     */
    public static function delete(
        ?Order $child,
        ?BillingCharge $charge,
        string $entryPoint,
        User $actor,
        ?array $disposition = null,
    ): array {
        $child  ??= $charge?->child_order_id ? Order::withTrashed()->find($charge->child_order_id) : null;
        $charge ??= $child ? self::chargeForChild($child) : null;

        $childActive  = $child && !$child->trashed();
        $chargeActive = $charge && !$charge->trashed();

        if (!$childActive && !$chargeActive) {
            return ['deleted' => false, 'already_deleted' => true];
        }

        $paymentState = self::paymentState($charge, $child);

        return DB::transaction(function () use (
            $child, $charge, $childActive, $chargeActive, $entryPoint, $actor, $disposition, $paymentState
        ) {
            // Order::deleting cascade soft-deletes the child's own payments
            // (incl. the extension's pending placeholder), history, addresses…
            if ($childActive) {
                $child->delete();
            }

            if ($chargeActive) {
                $charge->delete();
            }

            self::writeParentHistory($child, $charge, $entryPoint, $actor, $disposition, $paymentState);

            Log::channel('billing_engine')->info(
                'Extension transaction deleted'
                . ' | charge=' . ($charge->unique_id ?? 'none')
                . ' | child_order=' . ($child->order_number ?? 'none')
                . " | entry_point={$entryPoint}"
                . " | payment_state={$paymentState}"
                . " | by={$actor->id}"
                . ($disposition ? " | disposition={$disposition['processed_reason_code']}" : '')
            );

            return ['deleted' => true, 'already_deleted' => false];
        });
    }

    private static function writeParentHistory(
        ?Order $child,
        ?BillingCharge $charge,
        string $entryPoint,
        User $actor,
        ?array $disposition,
        string $paymentState,
    ): void {
        $parent = $charge?->parentOrder ?? $child?->referenceOrder;

        if (!$parent) {
            return;
        }

        $childNumber = $child?->order_number ?? '(missing)';
        $amount      = $charge ? ($charge->amount + $charge->tax_amount) : null;

        $description = "Extension charge {$childNumber} deleted by {$actor->full_name}"
            . ($amount !== null ? ' | Total: $' . number_format($amount, 2) : '')
            . ' | Payment state: ' . str_replace('_', ' ', $paymentState)
            . ' | Via: ' . ($entryPoint === self::ENTRY_BILLING_ROW ? 'Billing Engine row' : 'child order delete');

        if ($disposition) {
            $description .= '. Processed by ' . $disposition['processed_by_name']
                . ' (Employee ID verified). Reason: ' . $disposition['processed_reason_label']
                . (filled($disposition['processed_reason_other']) ? ' — ' . $disposition['processed_reason_other'] : '')
                . '.';
        }

        $parent->history()->create([
            'customer_id' => $parent->customer_id,
            'user_id'     => $actor->id,
            'action_by'   => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action'      => OrderHistoryAction::ExtensionChargeDeleted,
            'description' => $description,
            'extras'      => json_encode(array_merge([
                'entry_point'         => $entryPoint,
                'payment_state'       => $paymentState,
                'charge_unique_id'    => $charge?->unique_id,
                'parent_order_id'     => $parent->id,
                'parent_order_number' => $parent->order_number,
                'child_order_id'      => $child?->id,
                'child_order_number'  => $child?->order_number,
                'deleted_by_id'       => $actor->id,
                'deleted_by_name'     => $actor->full_name,
            ], $disposition ?? [])),
        ]);
    }
}
