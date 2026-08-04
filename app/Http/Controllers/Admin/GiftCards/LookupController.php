<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Http\Controllers\Controller;
use App\Models\GiftCards\GiftCard;
use App\Models\Orders\Order;
use App\Services\GiftCards\GiftCardPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Validate a gift card at the point of payment, before any money moves.
 *
 * ── WHY THIS IS SEPARATE FROM REDEMPTION ──────────────────────────────────
 *
 * The cashier types a number and needs to know, immediately: does this card
 * exist, can it be used, and how much is on it. Answering that by attempting
 * a redemption would mean discovering "insufficient balance" only after
 * committing to an amount. This endpoint answers the question without
 * touching the ledger.
 *
 * It is READ ONLY. Nothing here writes, locks or reserves. The authoritative
 * balance check happens again inside
 * {@see \App\Services\GiftCards\GiftCardService::redeem()}, under a row lock —
 * because between this lookup and that redemption the card may have been
 * spent at another register.
 *
 * ── RATE LIMITED ──────────────────────────────────────────────────────────
 *
 * A card number plus a PIN is a bearer instrument. An endpoint that will
 * confirm whether a guessed number exists is an enumeration oracle, so
 * attempts are throttled per user.
 */
class LookupController extends Controller
{
    public function __invoke(Request $request)
    {
        GiftCardPermissions::assert(auth()->user(), GiftCardPermissions::REDEEM);

        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:64'],
            'pin' => ['nullable', 'string', 'max:12'],
            'order_unique_id' => ['nullable', 'string', 'max:64'],
        ]);

        $key = 'gift-card-lookup:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many gift card lookups. Wait a minute and try again.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $card = GiftCard::where('card_number', trim($validated['card_number']))->first();

        if ($card === null) {
            return response()->json([
                'success' => false,
                'message' => 'No gift card was found with that number.',
            ], 404);
        }

        // A PIN, where one was set, is required before any balance is shown —
        // otherwise the number alone reveals what the card is worth.
        if (filled($card->pin_hash)) {
            if (blank($validated['pin'] ?? null) || ! Hash::check($validated['pin'], $card->pin_hash)) {
                return response()->json([
                    'success' => false,
                    'requires_pin' => true,
                    'message' => blank($validated['pin'] ?? null)
                        ? 'This gift card has a PIN. Enter it to continue.'
                        : 'That PIN does not match this gift card.',
                ], 422);
            }
        }

        // Balance from the LEDGER, never the cached column — the cashier is
        // about to make a decision with this number.
        $balance = $card->availableBalance();

        $orderBalance = null;
        if (filled($validated['order_unique_id'] ?? null)) {
            $order = Order::where('unique_id', $validated['order_unique_id'])->first();
            $orderBalance = $order ? round((float) $order->balance_due, 2) : null;
        }

        // The most that can be applied is bounded by both sides. Offering it
        // here means the cashier is not asked to work it out.
        $maxApplicable = $orderBalance === null
            ? $balance
            : round(min($balance, $orderBalance), 2);

        return response()->json([
            'success' => true,
            'card_number' => $card->card_number,
            'masked_number' => $card->maskedNumber(),
            'status' => $card->status->value,
            'status_label' => $card->status->label(),
            'issuance_class' => $card->issuance_class->value,
            'issuance_label' => $card->issuance_class->label(),
            'recipient' => $card->recipient_name,
            'balance' => $balance,
            'balance_formatted' => number_format($balance, 2),
            'order_balance' => $orderBalance,
            'max_applicable' => max($maxApplicable, 0),
            'redeemable' => $card->isRedeemable() && $balance > 0,

            // Why it cannot be used, when it cannot — so the cashier can tell
            // the customer something better than "it didn't work".
            'blocked_reason' => match (true) {
                ! $card->status->isRedeemable() => 'This card is '.strtolower($card->status->label()).'.',
                $balance <= 0 => 'This card has no remaining value.',
                default => null,
            },
        ]);
    }
}
