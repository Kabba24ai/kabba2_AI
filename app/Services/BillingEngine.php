<?php

namespace App\Services;

use App\Events\Admin\Billing\BillingChargeCreatedEvent;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Orders\BillingCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingEngine
{
    /**
     * Create a billing charge.
     *
     * If an idempotency_key is provided and a BillingCharge with that key
     * already exists, the existing charge is returned without side effects.
     * This makes mobile offline retries and webhook replays safe by design.
     *
     * The charge is created inside a transaction. The BillingChargeCreatedEvent
     * is fired after the transaction commits.
     *
     * Bridge mode (Phases 2–3): callers that need the legacy CustomerAccount
     * row to also be written should call BillingEngine::charge() and then
     * pass the returned BillingCharge to BillingEngine::writeLegacyBridge().
     * The bridge writes are NOT in scope for this foundation phase.
     */
    public static function charge(BillingChargeRequest $request): BillingCharge
    {
        // ── Idempotency check ──────────────────────────────────────────────
        if ($request->idempotencyKey !== null) {
            $existing = BillingCharge::where('idempotency_key', $request->idempotencyKey)->first();

            if ($existing !== null) {
                Log::channel('billing_engine')->info(
                    "BillingEngine idempotency hit | key={$request->idempotencyKey} " .
                    "| existing_unique_id={$existing->unique_id} — returning existing charge"
                );
                return $existing;
            }
        }

        // ── Create charge ──────────────────────────────────────────────────
        $charge = DB::transaction(function () use ($request): BillingCharge {
            return BillingCharge::create([
                'billing_charge_type'    => $request->type,
                'status'                 => 'pending',
                'parent_order_id'        => $request->orderId,
                'child_order_id'         => $request->childOrderId,
                'customer_id'            => $request->customerId,
                'store_id'               => $request->storeId,
                'order_product_id'       => $request->orderProductId,
                'amount'                 => $request->amount,
                'tax_amount'             => $request->taxAmount ?? 0,
                'tax_type'               => $request->taxType,
                'responsible_person_id'  => $request->responsiblePersonId,
                'notes'                  => $request->notes,
                'customer_account_id'    => $request->customerAccountId,
                'source_module'          => $request->sourceModule,
                'source_event'           => $request->sourceEvent,
                'source_reference_type'  => $request->sourceReferenceType,
                'source_reference_id'    => $request->sourceReferenceId,
                'metadata'               => $request->metadata,
                'idempotency_key'        => $request->idempotencyKey,
            ]);
        });

        Log::channel('billing_engine')->info(
            "BillingEngine charge created | unique_id={$charge->unique_id} " .
            "| type={$request->type} | amount={$request->amount} " .
            "| source_module={$request->sourceModule} | source_event={$request->sourceEvent} " .
            "| idempotency_key={$request->idempotencyKey}"
        );

        event(new BillingChargeCreatedEvent($charge));

        return $charge;
    }

    /**
     * Mark a billing charge as paid.
     * Status transition: pending → paid
     *
     * paid_at is set to now() if not already populated — preserves the original
     * payment date if markPaid() is called more than once (idempotent).
     */
    public static function markPaid(BillingCharge $charge): BillingCharge
    {
        $charge->status  = 'paid';
        $charge->paid_at = $charge->paid_at ?? now();
        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge marked paid | unique_id={$charge->unique_id} | paid_at={$charge->paid_at}"
        );

        return $charge;
    }

    /**
     * Mark a billing charge as resolved (waived / written off).
     * Status transition: pending → resolved
     */
    public static function markResolved(BillingCharge $charge, string $note, int $userId): BillingCharge
    {
        $charge->status = 'resolved';
        $charge->notes  = $note;
        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge resolved | unique_id={$charge->unique_id} | user_id={$userId}"
        );

        return $charge;
    }

    /**
     * Mark a billing charge as voided — the collection it represented was
     * reversed at the gateway before settlement (an in-place void, not a
     * refund). Status transition: any → voided.
     *
     * The canonical counterpart to markPaid() for the reversal direction:
     * when an extension child order's card payment is VOIDED, its parent
     * Extension charge must stop reading "Paid" (ExtensionPaymentSyncService
     * ::revertParentChargeOnVoid calls this). paid_at is intentionally left
     * as-is: it is the historical record of when the (now-voided) payment
     * settled, and every revenue/tax report already gates extension charges
     * on the child order still holding a live 'Paid' payment (the settled-
     * extension guard), so status='voided' — not the presence of paid_at —
     * is what keeps a voided extension out of those totals.
     *
     * Idempotent: a charge already voided is returned unchanged.
     */
    public static function markVoided(BillingCharge $charge, ?int $userId = null): BillingCharge
    {
        if ($charge->status === \App\Enums\Billing\BillingChargeStatus::Voided) {
            return $charge;
        }

        $charge->status = 'voided';
        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge voided | unique_id={$charge->unique_id} | user_id=" . ($userId ?? 'null')
        );

        return $charge;
    }

    /**
     * Mark a billing charge as uncollectible.
     * Status transition: pending → uncollectible
     */
    public static function markUncollectible(BillingCharge $charge, int $userId): BillingCharge
    {
        $charge->status = 'uncollectible';
        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge uncollectible | unique_id={$charge->unique_id} | user_id={$userId}"
        );

        return $charge;
    }

    /**
     * Look up a billing charge by idempotency key.
     * Returns null if no match exists.
     */
    public static function findByIdempotencyKey(string $key): ?BillingCharge
    {
        return BillingCharge::where('idempotency_key', $key)->first();
    }
}
