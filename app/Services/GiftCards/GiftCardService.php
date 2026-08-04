<?php

namespace App\Services\GiftCards;

use App\Enums\GiftCards\GiftCardIssuanceClass;
use App\Enums\GiftCards\GiftCardStatus;
use App\Enums\GiftCards\GiftCardTransactionType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\GiftCards\GiftCard;
use App\Models\GiftCards\GiftCardTransaction;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only component permitted to move gift-card value.
 *
 * ── WHAT A GIFT CARD IS, IN THIS SYSTEM ───────────────────────────────────
 *
 * Tender. It pays a price that has already been settled — subtotal, pre-tax
 * adjustments, taxable basis and tax are all final before a card is presented.
 * It never enters `app/Services/Discounts/`, never appears in `DiscountType`,
 * and never changes what an order costs or what tax it owes.
 *
 * ── TWO MONEY EVENTS, COUNTED ONCE EACH ───────────────────────────────────
 *
 *   Issuance (purchased) — real cash in, NO revenue, NO tax, liability opened.
 *   Redemption           — NO cash, revenue recognised, liability drawn down.
 *
 * Getting this wrong in either direction is a reporting failure the business
 * would feel: counting redemption as cash double-counts money that arrived at
 * issuance; excluding redemption from revenue deletes real sales and the tax
 * owed on them. See docs/gift-cards/REPORTING_TREATMENT.md.
 *
 * A purchased card is funded through a STANDALONE record — never an order,
 * never an `order_product`, never a catalogue item. That is what keeps it
 * structurally invisible to `CollectedRevenueQuery::qualifyingPayments()`,
 * which requires order lines to exist. The exclusion is a property of the
 * design rather than a predicate someone must remember to write.
 *
 * ── LOCK ORDER ────────────────────────────────────────────────────────────
 *
 * `gift_cards` → `orders` → `order_payments`, always, without exception.
 * A consistent order is what prevents two concurrent redemptions from forming
 * a wait cycle. Nothing outside this service locks `gift_cards`, and no cycle
 * exists with Goodwill (which locks `customers` → `orders` → `order_payments`):
 * both reach `orders` from a different first lock, so one simply waits.
 */
class GiftCardService
{
    /** MySQL/MariaDB: lock wait timeout exceeded. */
    private const LOCK_WAIT_TIMEOUT = 1205;

    /** SQLSTATE for deadlock — a retryable, expected outcome under contention. */
    private const DEADLOCK = '40001';

    private const MAX_DEADLOCK_RETRIES = 5;

    private const DEFAULT_PREFIX = 'GC';

    // ── Authority ─────────────────────────────────────────────────────────

    /**
     * Who is performing this operation.
     *
     * Falls back to the signed-in user so ordinary call sites need not thread
     * one through, while a queued job or console command can pass an explicit
     * actor. Null actor means null permissions, which means refused — the
     * service is the enforcement point, not the controller above it. That
     * matters most for {@see self::grant()} and {@see self::adjustIncrease()},
     * which create spendable value from nothing.
     */
    private static function actor(?Authenticatable $actor = null): ?Authenticatable
    {
        return $actor ?? auth()->user();
    }

    private static function actorId(?Authenticatable $actor): ?int
    {
        return $actor?->getAuthIdentifier();
    }

    /** Value-changing operations must state why. Never a silent ledger write. */
    private static function assertReason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw GiftCardException::because(GiftCardFailure::ReasonRequired);
        }

        return $reason;
    }

    // ── Issuance ──────────────────────────────────────────────────────────

    /**
     * Sell a gift card. Real money in; nothing sold.
     *
     * The funding tender is a REAL method — Cash, Card, Cheque — never
     * `GiftCard`, which means redemption. Funding a card with a card would
     * create value from nothing and make reconciliation Stream E circular; the
     * storage layer refuses it too.
     *
     * @param  string|null  $fundingTransactionId  Processor reference, where one exists.
     *                                             This is what lets Stream E reconcile to
     *                                             an Authorize.Net settlement.
     * @param  string|null  $idempotencyKey  A retried request returns the existing card
     *                                       rather than issuing a second one.
     */
    public static function purchase(
        float $amount,
        OrderPaymentMethod $fundingMethod,
        ?int $purchaserCustomerId = null,
        ?string $recipientName = null,
        ?string $senderName = null,
        ?string $recipientEmail = null,
        ?string $message = null,
        ?int $issuedByStoreId = null,
        ?string $fundingTransactionId = null,
        ?string $fundedAt = null,
        ?string $idempotencyKey = null,
        ?int $createdById = null,
        ?Authenticatable $actor = null,
    ): GiftCard {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::SELL);

        $createdById ??= self::actorId($actor);

        self::assertPositive($amount);

        if ($fundingMethod === OrderPaymentMethod::GiftCard) {
            throw GiftCardException::because(GiftCardFailure::FundingMethodInvalid);
        }

        $key = $idempotencyKey ?? 'gc-purchase-'.Str::uuid()->toString();

        if ($existing = self::existingCardFor($key)) {
            return $existing;
        }

        $at = $fundedAt ? \Illuminate\Support\Carbon::parse($fundedAt) : now();

        return (new self)->inReadCommittedTransaction(function () use (
            $amount, $fundingMethod, $purchaserCustomerId, $recipientName, $senderName,
            $recipientEmail, $message, $issuedByStoreId, $fundingTransactionId, $at, $key, $createdById
        ) {
            $card = GiftCard::create([
                'card_number' => self::generateCardNumber(),
                'lookup_token' => self::generateLookupToken(),
                'issuance_class' => GiftCardIssuanceClass::Purchased->value,
                'original_value' => $amount,
                'cached_balance' => $amount,
                'status' => GiftCardStatus::Active->value,
                'purchaser_customer_id' => $purchaserCustomerId,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'sender_name' => $senderName,
                'message' => $message,
                'issued_by_store_id' => $issuedByStoreId,
                'activated_at' => $at,
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            GiftCardTransaction::create([
                'gift_card_id' => $card->id,
                'type' => GiftCardTransactionType::IssuancePurchased->value,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => $amount,

                // The real tender and the cash behind it. `funding_cash_amount`
                // is held apart from `amount` because the card's value and the
                // money kept are different questions — a later chargeback
                // reduces the cash without changing what the card is worth.
                'funding_payment_method' => $fundingMethod->value,
                'funding_transaction_id' => $fundingTransactionId,
                'funding_cash_amount' => $amount,

                'idempotency_key' => $key,
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
                'created_at' => $at,
            ]);

            return $card->fresh();
        });
    }

    /**
     * Give a gift card away. No money, no funding row, no liability.
     *
     * Merchant-funded promotional value. It redeems exactly like a purchased
     * card — tender, applied after tax, order fully taxed — but it must stay
     * distinguishable from purchased liability everywhere downstream, which is
     * why the CARD carries the class and reporting reads it through the
     * redemption's card rather than inferring anything from the payment row.
     */
    public static function grant(
        float $amount,
        string $reasonCode,
        string $reasonCategory,
        ?string $note = null,
        ?int $recipientCustomerId = null,
        ?string $recipientName = null,
        ?string $recipientEmail = null,
        ?string $message = null,
        ?int $issuedByStoreId = null,
        ?string $idempotencyKey = null,
        ?int $createdById = null,
        ?Authenticatable $actor = null,
    ): GiftCard {
        // The single most consequential permission in this feature: granting
        // creates spendable value that nobody paid for. Deliberately separate
        // from SELL — taking $500 and issuing $500 is a clerk's job; issuing
        // $500 out of nothing is not.
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::GRANT);

        $createdById ??= self::actorId($actor);

        self::assertPositive($amount);

        if (trim($reasonCode) === '' || trim($reasonCategory) === '') {
            throw GiftCardException::because(GiftCardFailure::GrantReasonRequired);
        }

        $key = $idempotencyKey ?? 'gc-grant-'.Str::uuid()->toString();

        if ($existing = self::existingCardFor($key)) {
            return $existing;
        }

        return (new self)->inReadCommittedTransaction(function () use (
            $amount, $reasonCode, $reasonCategory, $note, $recipientCustomerId,
            $recipientName, $recipientEmail, $message, $issuedByStoreId, $key, $createdById
        ) {
            $card = GiftCard::create([
                'card_number' => self::generateCardNumber(),
                'lookup_token' => self::generateLookupToken(),
                'issuance_class' => GiftCardIssuanceClass::Granted->value,
                'grant_reason_code' => $reasonCode,
                'grant_reason_category' => $reasonCategory,
                'grant_note' => $note,
                'original_value' => $amount,
                'cached_balance' => $amount,
                'status' => GiftCardStatus::Active->value,
                'recipient_customer_id' => $recipientCustomerId,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'message' => $message,
                'issued_by_store_id' => $issuedByStoreId,
                'activated_at' => now(),
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            GiftCardTransaction::create([
                'gift_card_id' => $card->id,
                'type' => GiftCardTransactionType::IssuanceGranted->value,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => $amount,
                // No funding columns. The storage layer refuses them on a
                // granted row — promotional value is never cash.
                'idempotency_key' => $key,
                'reason' => $reasonCode,
                'note' => $note,
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            return $card->fresh();
        });
    }

    // ── Redemption ────────────────────────────────────────────────────────

    /**
     * Spend gift-card value against an order.
     *
     * Produces an ORDINARY `OrderPayment` with method `GiftCard`, linked back
     * to the ledger row that authorised it. Ordinary on purpose: the payment
     * must appear in payment history, on the receipt, and in the order's
     * settled total exactly like any other tender — the order genuinely is
     * paid. Only the CASH reporting differs, and that is resolved downstream
     * by reading this link, never by treating the payment as special here.
     *
     * The card is locked BEFORE its balance is read. Checking an unlocked
     * balance and then debiting is the classic overspend: two concurrent
     * requests both read $500, both approve, and $1,000 leaves a $500 card.
     */
    public static function redeem(
        GiftCard|string $card,
        Order $order,
        float $amount,
        ?string $idempotencyKey = null,
        ?int $createdById = null,
        ?string $note = null,
        ?Authenticatable $actor = null,
    ): GiftCardTransaction {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::REDEEM);

        $createdById ??= self::actorId($actor);

        self::assertPositive($amount);

        $key = $idempotencyKey ?? 'gc-redeem-'.Str::uuid()->toString();

        if ($existing = GiftCardTransaction::where('idempotency_key', $key)->first()) {
            return $existing;
        }

        return (new self)->inReadCommittedTransaction(function () use ($card, $order, $amount, $key, $createdById, $note) {
            // 1. Lock the card first — the canonical lock order.
            $locked = self::lockCard($card);

            // 2. Status is a necessary condition, checked before the money.
            if (! $locked->isRedeemable()) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::CardNotRedeemable,
                    'Status: '.$locked->status->label().'.'
                );
            }

            // 3. Balance from the LEDGER, under the lock. Never the cache —
            //    a stale projection reading high is a card spent twice.
            $available = round((float) GiftCardTransaction::where('gift_card_id', $locked->id)->sum('amount'), 2);

            if (self::cents($amount) > self::cents($available)) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::InsufficientBalance,
                    'Available: '.number_format($available, 2).'.'
                );
            }

            // 4. Lock the order, then read what it still owes. Both bounds
            //    matter: a card may not overpay an order any more than an
            //    order may overdraw a card.
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $balanceDue = round((float) $lockedOrder->balance_due, 2);

            if (self::cents($balanceDue) <= 0) {
                throw GiftCardException::because(GiftCardFailure::OrderNotPayable);
            }

            if (self::cents($amount) > self::cents($balanceDue)) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::ExceedsOrderBalance,
                    'Balance due: '.number_format($balanceDue, 2).'.'
                );
            }

            // 5. The ordinary payment. Settled on creation — gift-card value
            //    is already in hand, so there is nothing to authorise and no
            //    processor to wait for.
            $fullySettles = self::cents($amount) >= self::cents($balanceDue);

            $payment = $lockedOrder->payments()->create([
                'payment_method' => OrderPaymentMethod::GiftCard->value,
                'payment_datetime' => now(),
                'amount' => $amount,
                'status' => $fullySettles
                    ? OrderPaymentStatus::Paid->value
                    : OrderPaymentStatus::PartialPayment->value,
                'payment_note' => trim('Gift Card '.$locked->card_number.' '.(string) $note),
                'idempotency_token' => $key,
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            // 6. The ledger row, linked to the payment it authorised. This
            //    link is what lets reporting classify the payment as non-cash
            //    tender and tell purchased liability from granted promotion.
            $transaction = GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'type' => GiftCardTransactionType::Redemption->value,
                'amount' => -$amount,
                'balance_before' => $available,
                'balance_after' => round($available - $amount, 2),
                'order_id' => $lockedOrder->id,
                'order_payment_id' => $payment->id,
                'idempotency_key' => $key,
                'note' => $note,
                'created_by_id' => $createdById,
                'created_by_type' => $createdById ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            self::refreshProjection($locked);

            return $transaction;
        });
    }

    // ── Refund routing ────────────────────────────────────────────────────

    /**
     * Return refunded value to the card that paid it.
     *
     * ── WHY THIS IS THE DEFAULT AND CASH IS NOT ───────────────────────────
     *
     * A customer buys a $500 card, spends it, then cancels. Refund $500 in
     * cash and they hold $500 of money for $500 of stored value — the business
     * has converted a liability into cash it never took for that transaction,
     * and a granted card converts promotional value the customer never paid
     * for into money. Repeatedly, that is a way to launder store credit into
     * the till.
     *
     * So value returns to the card it came from. Sending it anywhere else is
     * possible but is a separate, privileged decision — see
     * {@see GiftCardPermissions::canRefundOffCard()}, which requires ADJUST
     * (manufacture/destroy value), not REDEEM (spend it), because that is what
     * it actually is.
     *
     * ── BOUNDED BY WHAT THE CARD ACTUALLY PAID ────────────────────────────
     *
     * A refund can never return more than this order took from this card.
     * Without that bound a refund is an unbounded top-up: a $10 card that paid
     * $10 could be refunded $500 by typing the wrong figure.
     *
     * Idempotent, so a replayed refund cannot credit twice.
     */
    public static function refundToCard(
        OrderPayment $giftCardPayment,
        float $amount,
        string $reason,
        ?string $idempotencyKey = null,
        ?Authenticatable $actor = null,
    ): GiftCardTransaction {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::REDEEM);

        self::assertPositive($amount);
        $reason = self::assertReason($reason);

        $key = $idempotencyKey ?? 'gc-refund-'.Str::uuid()->toString();

        if ($existing = GiftCardTransaction::where('idempotency_key', $key)->first()) {
            return $existing;
        }

        // The redemption this payment came from. Its absence means the payment
        // never drew on a card — refunding "to the card" is then meaningless.
        $redemption = GiftCardTransaction::where('order_payment_id', $giftCardPayment->id)
            ->where('type', GiftCardTransactionType::Redemption->value)
            ->first();

        if ($redemption === null) {
            throw GiftCardException::because(GiftCardFailure::NothingToRefund);
        }

        return (new self)->inReadCommittedTransaction(function () use (
            $redemption, $amount, $reason, $key, $actor
        ) {
            $card = self::lockCard(GiftCard::findOrFail($redemption->gift_card_id));

            // What this order took, less what has already been given back.
            $redeemed = abs(self::cents($redemption->amount));
            $alreadyRefunded = abs(self::cents(
                GiftCardTransaction::where('reverses_transaction_id', $redemption->id)->sum('amount')
            ));
            $refundable = $redeemed - $alreadyRefunded;

            if (self::cents($amount) > $refundable) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::RefundExceedsRedeemed,
                    'Refundable: '.number_format($refundable / 100, 2).'.'
                );
            }

            $before = round((float) GiftCardTransaction::where('gift_card_id', $card->id)->sum('amount'), 2);

            $txn = GiftCardTransaction::create([
                'gift_card_id' => $card->id,
                'type' => GiftCardTransactionType::RefundToCard->value,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => round($before + $amount, 2),
                'order_id' => $redemption->order_id,
                'reverses_transaction_id' => $redemption->id,
                'idempotency_key' => $key,
                'reason' => $reason,
                'created_by_id' => self::actorId($actor),
                'created_by_type' => $actor ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            // A card spent to zero and then refunded becomes spendable again.
            // Terminal states are not walked back — see refreshProjection().
            if ($card->status === GiftCardStatus::FullyRedeemed) {
                $card->forceFill(['status' => GiftCardStatus::PartiallyRedeemed->value])->save();
            }

            self::refreshProjection($card);

            return $txn;
        });
    }

    /**
     * May this payment's value be refunded somewhere other than the card?
     *
     * A question, not a permission check — the caller still has to hold
     * ADJUST. Used by a refund screen to decide whether to OFFER the choice.
     */
    public static function requiresRefundToCard(OrderPayment $payment): bool
    {
        return GiftCardTransaction::where('order_payment_id', $payment->id)
            ->where('type', GiftCardTransactionType::Redemption->value)
            ->exists();
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────

    /**
     * Block a card without destroying its value.
     *
     * Reversible, and deliberately distinct from cancellation: a suspected
     * fraud or a disputed charge should stop the card moving while the question
     * is open, not write off money that may turn out to be legitimately owed.
     *
     * Moves no value, so it writes no ledger row — the audit lives on the card
     * itself. See 2026_08_03_100002.
     */
    public static function suspend(GiftCard $card, string $reason, ?Authenticatable $actor = null): GiftCard
    {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::SUSPEND);
        $reason = self::assertReason($reason);

        return (new self)->inReadCommittedTransaction(function () use ($card, $reason, $actor) {
            $locked = self::lockCard($card);

            if ($locked->status === GiftCardStatus::Suspended) {
                throw GiftCardException::because(GiftCardFailure::AlreadyInThatState);
            }

            if ($locked->status instanceof GiftCardStatus && $locked->status->isTerminal()) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::CardNotRedeemable,
                    'A '.$locked->status->label().' card cannot be suspended.'
                );
            }

            $locked->forceFill([
                'status' => GiftCardStatus::Suspended->value,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'suspended_by' => self::actorId($actor),
            ])->save();

            return $locked->fresh();
        });
    }

    /** Return a suspended card to circulation. Equally consequential, equally audited. */
    public static function reinstate(GiftCard $card, string $reason, ?Authenticatable $actor = null): GiftCard
    {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::SUSPEND);
        $reason = self::assertReason($reason);

        return (new self)->inReadCommittedTransaction(function () use ($card, $reason, $actor) {
            $locked = self::lockCard($card);

            if ($locked->status !== GiftCardStatus::Suspended) {
                throw GiftCardException::because(GiftCardFailure::AlreadyInThatState);
            }

            $locked->forceFill([
                'reinstated_at' => now(),
                'reinstatement_reason' => $reason,
                'reinstated_by' => self::actorId($actor),
                // Status is not set here — refreshProjection() derives it from
                // the ledger, so a card that was spent to zero while suspended
                // reinstates to FullyRedeemed rather than falsely to Active.
                'status' => GiftCardStatus::Active->value,
            ])->save();

            return self::refreshProjection($locked);
        });
    }

    /**
     * Void a card permanently, writing off whatever it still holds.
     *
     * The write-off IS the audit entry: an explicit negative ledger row naming
     * who cancelled it and why. Silently zeroing a balance column would leave
     * money disappearing with no record of who removed it.
     */
    public static function cancel(GiftCard $card, string $reason, ?Authenticatable $actor = null): GiftCard
    {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::CANCEL);
        $reason = self::assertReason($reason);

        return (new self)->inReadCommittedTransaction(function () use ($card, $reason, $actor) {
            $locked = self::lockCard($card);

            if ($locked->status === GiftCardStatus::Cancelled) {
                throw GiftCardException::because(GiftCardFailure::AlreadyInThatState);
            }

            $balance = round((float) GiftCardTransaction::where('gift_card_id', $locked->id)->sum('amount'), 2);

            // Only if there is something to write off — `amount <> 0` at the
            // storage layer, and a zero row would record nothing anyway.
            if (self::cents($balance) > 0) {
                GiftCardTransaction::create([
                    'gift_card_id' => $locked->id,
                    'type' => GiftCardTransactionType::Cancellation->value,
                    'amount' => -$balance,
                    'balance_before' => $balance,
                    'balance_after' => 0.0,
                    'idempotency_key' => 'gc-cancel-'.$locked->id.'-'.Str::uuid()->toString(),
                    'reason' => $reason,
                    'created_by_id' => self::actorId($actor),
                    'created_by_type' => $actor ? \App\Models\Iam\Personnel\User::class : null,
                ]);
            }

            $locked->forceFill([
                'cached_balance' => 0,
                'status' => GiftCardStatus::Cancelled->value,
                'cancelled_at' => now(),
            ])->save();

            return $locked->fresh();
        });
    }

    /**
     * Move a card's remaining value onto a new card — lost, stolen, damaged.
     *
     * Two ledger rows, not one: value leaves the old card and arrives on the
     * new one, each visible on its own card's history. A single row would make
     * the money appear to vanish from one side.
     *
     * The old card is terminal afterwards, so a recovered original cannot be
     * spent alongside its replacement.
     */
    public static function replace(GiftCard $card, string $reason, ?Authenticatable $actor = null): GiftCard
    {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::REPLACE);
        $reason = self::assertReason($reason);

        return (new self)->inReadCommittedTransaction(function () use ($card, $reason, $actor) {
            $old = self::lockCard($card);

            if ($old->status instanceof GiftCardStatus && $old->status->isTerminal()) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::CardNotReplaceable,
                    'Status: '.$old->status->label().'.'
                );
            }

            $balance = round((float) GiftCardTransaction::where('gift_card_id', $old->id)->sum('amount'), 2);

            if (self::cents($balance) <= 0) {
                throw GiftCardException::because(GiftCardFailure::InsufficientBalance);
            }

            // The replacement inherits the ORIGINAL's class and recipient. A
            // replaced granted card must not silently become purchased
            // liability, and the value must not change hands.
            $new = GiftCard::create([
                'card_number' => self::generateCardNumber(),
                'lookup_token' => self::generateLookupToken(),
                'issuance_class' => $old->issuance_class instanceof GiftCardIssuanceClass
                    ? $old->issuance_class->value
                    : $old->issuance_class,
                'grant_reason_code' => $old->grant_reason_code,
                'grant_reason_category' => $old->grant_reason_category,
                'original_value' => $balance,
                'cached_balance' => $balance,
                'status' => GiftCardStatus::Active->value,
                'purchaser_customer_id' => $old->purchaser_customer_id,
                'recipient_customer_id' => $old->recipient_customer_id,
                'recipient_name' => $old->recipient_name,
                'recipient_email' => $old->recipient_email,
                'sender_name' => $old->sender_name,
                'message' => $old->message,
                'issued_by_store_id' => $old->issued_by_store_id,
                'activated_at' => now(),
                'created_by_id' => self::actorId($actor),
                'created_by_type' => $actor ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            $stamp = Str::uuid()->toString();
            $by = ['created_by_id' => self::actorId($actor),
                   'created_by_type' => $actor ? \App\Models\Iam\Personnel\User::class : null];

            GiftCardTransaction::create(array_merge($by, [
                'gift_card_id' => $old->id,
                'type' => GiftCardTransactionType::ReplacementTransfer->value,
                'amount' => -$balance,
                'balance_before' => $balance,
                'balance_after' => 0.0,
                'idempotency_key' => 'gc-replace-out-'.$stamp,
                'reason' => $reason,
                'note' => 'Transferred to '.$new->card_number,
            ]));

            GiftCardTransaction::create(array_merge($by, [
                'gift_card_id' => $new->id,
                'type' => GiftCardTransactionType::ReplacementTransfer->value,
                'amount' => $balance,
                'balance_before' => 0.0,
                'balance_after' => $balance,
                'idempotency_key' => 'gc-replace-in-'.$stamp,
                'reason' => $reason,
                'note' => 'Transferred from '.$old->card_number,
            ]));

            $old->forceFill([
                'cached_balance' => 0,
                'status' => GiftCardStatus::Replaced->value,
                'replaced_by_gift_card_id' => $new->id,
            ])->save();

            return $new->fresh();
        });
    }

    // ── Manual adjustment ─────────────────────────────────────────────────

    /**
     * Add value by hand.
     *
     * The same power as {@see self::grant()} in a different shape — it creates
     * spendable money from nothing — so it carries the same permission weight
     * and the same non-negotiable reason. The ledger row is the audit entry.
     */
    public static function adjustIncrease(
        GiftCard $card,
        float $amount,
        string $reason,
        ?string $note = null,
        ?Authenticatable $actor = null,
    ): GiftCardTransaction {
        return self::adjust($card, $amount, GiftCardTransactionType::AdjustmentIncrease, $reason, $note, $actor);
    }

    /** Remove value by hand. Bounded by the balance — it cannot go negative. */
    public static function adjustDecrease(
        GiftCard $card,
        float $amount,
        string $reason,
        ?string $note = null,
        ?Authenticatable $actor = null,
    ): GiftCardTransaction {
        return self::adjust($card, $amount, GiftCardTransactionType::AdjustmentDecrease, $reason, $note, $actor);
    }

    private static function adjust(
        GiftCard $card,
        float $amount,
        GiftCardTransactionType $type,
        string $reason,
        ?string $note,
        ?Authenticatable $actor,
    ): GiftCardTransaction {
        $actor = self::actor($actor);
        GiftCardPermissions::assert($actor, GiftCardPermissions::ADJUST);

        self::assertPositive($amount);
        $reason = self::assertReason($reason);

        return (new self)->inReadCommittedTransaction(function () use ($card, $amount, $type, $reason, $note, $actor) {
            $locked = self::lockCard($card);

            $before = round((float) GiftCardTransaction::where('gift_card_id', $locked->id)->sum('amount'), 2);
            $signed = $type->isCredit() ? $amount : -$amount;
            $after = round($before + $signed, 2);

            if (self::cents($after) < 0) {
                throw GiftCardException::withDetail(
                    GiftCardFailure::InsufficientBalance,
                    'Available: '.number_format($before, 2).'.'
                );
            }

            $txn = GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'type' => $type->value,
                'amount' => $signed,
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => 'gc-adjust-'.$locked->id.'-'.Str::uuid()->toString(),
                'reason' => $reason,
                'note' => $note,
                'created_by_id' => self::actorId($actor),
                'created_by_type' => $actor ? \App\Models\Iam\Personnel\User::class : null,
            ]);

            // An increase can raise the balance above the original face value,
            // which `gc_cached_balance_within_bounds` forbids. The face value
            // is what the card is now worth, so it rises with a deliberate
            // top-up — this is the one path allowed to move it, and only
            // upward, and only before the CHECK could reject the projection.
            if (self::cents($after) > self::cents($locked->original_value)) {
                $locked->forceFill(['original_value' => $after])->saveQuietly();
            }

            self::refreshProjection($locked->fresh());

            return $txn;
        });
    }

    // ── Projection ────────────────────────────────────────────────────────

    /**
     * Recompute the cached balance and status from the ledger.
     *
     * Called inside the same locked transaction that appended the row, so the
     * cache can never be published ahead of the truth it summarises.
     */
    public static function refreshProjection(GiftCard $card): GiftCard
    {
        $balance = round((float) GiftCardTransaction::where('gift_card_id', $card->id)->sum('amount'), 2);

        $status = $card->status;

        // Terminal states are not walked back by a balance change — a
        // cancelled card that receives a refund is still cancelled, and that
        // is a decision for an operator, not an arithmetic side effect.
        if ($status instanceof GiftCardStatus && ! $status->isTerminal() && $status !== GiftCardStatus::Suspended) {
            $status = match (true) {
                self::cents($balance) <= 0 => GiftCardStatus::FullyRedeemed,
                self::cents($balance) < self::cents($card->original_value) => GiftCardStatus::PartiallyRedeemed,
                default => GiftCardStatus::Active,
            };
        }

        $card->forceFill([
            'cached_balance' => $balance,
            'status' => $status instanceof GiftCardStatus ? $status->value : $status,
            'fully_redeemed_at' => self::cents($balance) <= 0
                ? ($card->fully_redeemed_at ?? now())
                : null,
        ])->save();

        return $card->fresh();
    }

    // ── Card identity ─────────────────────────────────────────────────────

    /**
     * A human-typable number with a configurable prefix, e.g. RNK-4820-9137.
     *
     * Never a sequential id: a card number is quoted over the phone and typed
     * at a register, and a guessable one is a redeemable one. Uniqueness is
     * guaranteed by the unique index; this retries on the vanishingly rare
     * collision rather than assuming one cannot happen.
     */
    public static function generateCardNumber(): string
    {
        $prefix = self::cardPrefix();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = sprintf('%s-%04d-%04d', $prefix, random_int(1000, 9999), random_int(1000, 9999));

            if (! GiftCard::where('card_number', $number)->exists()) {
                return $number;
            }
        }

        // Ten collisions is not bad luck; it is a signal the space is wrong.
        return sprintf('%s-%s', $prefix, strtoupper(Str::random(9)));
    }

    /** Company Settings, so the brand is not compiled into the code. */
    public static function cardPrefix(): string
    {
        $configured = Setting::where('setting_type', 'Company Settings')
            ->where('setting_name', 'gift_card_prefix')
            ->value('setting_value');

        $prefix = strtoupper(trim((string) $configured));

        return preg_match('/^[A-Z]{2,6}$/', $prefix) === 1 ? $prefix : self::DEFAULT_PREFIX;
    }

    /**
     * The opaque token behind a QR code.
     *
     * Deliberately separate from the card number so a scan can reveal a
     * balance without exposing the value that, with a PIN, redeems the card.
     */
    public static function generateLookupToken(): string
    {
        return Str::random(48);
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private static function lockCard(GiftCard|string $card): GiftCard
    {
        $query = GiftCard::query()->lockForUpdate();

        $locked = $card instanceof GiftCard
            ? $query->where('id', $card->id)->first()
            : $query->where('card_number', $card)->first();

        if ($locked === null) {
            throw GiftCardException::because(GiftCardFailure::CardNotFound);
        }

        return $locked;
    }

    /** The card an already-processed issuance produced, if the key was seen. */
    private static function existingCardFor(string $idempotencyKey): ?GiftCard
    {
        return GiftCardTransaction::where('idempotency_key', $idempotencyKey)
            ->first()?->giftCard;
    }

    private static function assertPositive(float $amount): void
    {
        if (self::cents($amount) <= 0) {
            throw GiftCardException::because(GiftCardFailure::AmountNotPositive);
        }
    }

    private static function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    /**
     * READ COMMITTED for the duration of one transaction, with a bounded
     * deadlock retry.
     *
     * ISOLATION SCOPE. `SET TRANSACTION ISOLATION LEVEL` with no `SESSION` or
     * `GLOBAL` applies to the NEXT transaction only, then the connection
     * reverts. Nothing outside this call is affected — important on a pooled
     * connection shared with the rest of the request.
     *
     * WHY READ COMMITTED. Under REPEATABLE READ, InnoDB takes gap locks, and a
     * range scan over `gift_card_transactions` for one card would lock gaps
     * that block inserts for OTHER cards entirely unrelated to this operation.
     * READ COMMITTED takes no gap locks, so contention stays on the row that
     * is genuinely contended — the card being spent.
     *
     * A deadlock here is expected under load, not exceptional: two operations
     * touching the same card from different directions. It is retried a bounded
     * number of times and then surfaced. A lock-wait timeout is NOT retried —
     * it means someone else has held the card long enough that the operator
     * should be told rather than left waiting again.
     */
    private function inReadCommittedTransaction(callable $callback)
    {
        $alreadyInTransaction = DB::transactionLevel() > 0;
        $attempt = 0;

        while (true) {
            if (! $alreadyInTransaction) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            }

            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) === self::LOCK_WAIT_TIMEOUT) {
                    throw GiftCardException::because(GiftCardFailure::Contended);
                }

                if ($e->getCode() === self::DEADLOCK && ++$attempt <= self::MAX_DEADLOCK_RETRIES) {
                    usleep(100000);

                    continue;
                }

                throw $e;
            }
        }
    }
}
