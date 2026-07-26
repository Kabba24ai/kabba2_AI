<?php

namespace App\Services\Credit;

use App\Enums\Credit\CreditThresholdSourceType;
use App\Events\Credit\CreditThresholdExceededEvent;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Services\Billing\AddChargeToAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observes committed A/R exposure increases and fires a CreditThresholdExceededEvent
 * when an APPROVED credit account crosses (or extends beyond) its limit.
 *
 * Called from the two — and only two — methods that increase outstanding A/R:
 * CustomHelper::updateCreditBalance() and LedgerBalanceService::applyTransaction().
 * The funnel audit proved those are the sole exposure writers, so this single
 * observation point has no bypass.
 *
 * Design guarantees:
 *  - APPROVED accounts only — reuses the canonical AddChargeToAccountService
 *    ::customerIsEligible() gate (is_credit_account && credit_limit > 0). CR-1
 *    defect rows and non-credit customers never fire.
 *  - Genuine NEW exposure only — requires a freshly-created ledger row, which
 *    excludes edit/reversal/repair re-writes (those reuse an existing row and
 *    are never new debt).
 *  - Fires on a first crossing AND on additional exposure while already over,
 *    but NEVER on a balance-neutral or decreasing update (exposure must be > 0).
 *  - Deferred past commit via DB::afterCommit so a rolled-back posting produces
 *    no event. The detection itself is fully guarded: a bug here can never roll
 *    back or break the financial posting.
 */
class CreditThresholdMonitor
{
    public static function observe(
        Customer $customer,
        float $balanceBefore,
        float $balanceAfter,
        CustomerAccount $record,
        CreditThresholdSourceType $sourceType,
    ): void {
        try {
            // Approved credit account only (canonical eligibility gate).
            if (! AddChargeToAccountService::customerIsEligible($customer)) {
                return;
            }

            // Genuine new exposure only: a brand-new ledger row. Edits, reversals
            // and repair re-writes reuse an existing row and must not fire.
            if (! $record->wasRecentlyCreated) {
                return;
            }

            $exposureAdded = round($balanceAfter - $balanceBefore, 2);
            if ($exposureAdded <= 0) {
                return; // balance-neutral or decreasing update — not new exposure
            }

            $limit = (float) $customer->credit_limit;
            if ($balanceAfter <= $limit) {
                return; // still within the approved threshold
            }

            // Qualifying: crossing (before <= limit) OR additional exposure while
            // already over (before > limit). Both are review-worthy.
            $orderId = $record->order_id ? (int) $record->order_id : null;
            $accountRowId = $record->id ? (int) $record->id : null;

            // Idempotency identifies the unique POSTING EPISODE — not the order,
            // not the customer.
            //
            //  - An ORDER BOOKING (checkout / convert-to-account) writes one
            //    type='order' ledger row PER LINE within a single posting; those
            //    lines collapse to ONE event, keyed by the order id.
            //  - Every OTHER exposure (fuel / damage / manual / extension charge,
            //    a later adjustment) is a single type='charge' ledger row and is
            //    keyed to that row (customer_accounts.id — the canonical A/R
            //    posting id). So a LATER charge on the SAME order is its OWN
            //    event and is never suppressed by the initial order event, while
            //    a genuine retry of the SAME posting (same row / same order
            //    booking) still resolves to one event.
            //
            // The narrowest safe canonical identity is used and no Order ID is
            // fabricated for non-order postings.
            $isOrderBooking = $record->type === 'order';
            $idempotencyKey = ($isOrderBooking && $orderId !== null)
                ? "order:{$orderId}"
                : 'acct:' . ($accountRowId ?? 'unknown');

            [$responsibleUserId, $responsibleContext] = self::resolveActor();

            $snapshot = new CreditThresholdSnapshot(
                idempotencyKey: $idempotencyKey,
                customerId: (int) $customer->id,
                customerName: $customer->full_name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: null,
                orderId: $orderId,
                accountRowId: $accountRowId,
                sourceType: $sourceType,
                sourceDetail: $record->reason ?: null,
                creditLimit: round($limit, 2),
                balanceBefore: round($balanceBefore, 2),
                exposureAdded: $exposureAdded,
                balanceAfter: round($balanceAfter, 2),
                amountOverLimit: round($balanceAfter - $limit, 2),
                responsibleUserId: $responsibleUserId,
                responsibleContext: $responsibleContext,
                occurredAt: now(),
            );

            // Fire only after the (possibly outer) transaction durably commits.
            // A rolled-back posting discards this callback → no event.
            DB::afterCommit(static function () use ($snapshot) {
                event(new CreditThresholdExceededEvent($snapshot));
            });
        } catch (\Throwable $e) {
            // A monitoring defect must never break or roll back a financial
            // posting. Swallow and report; the posting stands.
            Log::error('CreditThresholdMonitor failed to observe a posting', [
                'customer_id' => $record->customer_id ?? null,
                'account_row_id' => $record->id ?? null,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Best-effort attribution of who/what completed the posting, captured while
     * still in the request context. Staff actions carry a user id; customer and
     * guest checkout carry only a context marker.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private static function resolveActor(): array
    {
        if (auth()->check()) {
            $context = session()->has('impersonated_by_admin') ? 'impersonation' : null;

            return [(int) auth()->id(), $context];
        }

        if (auth('customer')->check()) {
            return [null, 'customer_portal'];
        }

        return [null, 'guest_or_system'];
    }
}
