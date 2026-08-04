<?php

namespace App\Http\Controllers\Admin\GiftCards\Granted;

use App\Http\Controllers\Admin\GiftCards\Concerns\HandlesGiftCardFailures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\GrantGiftCardRequest;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardService;

/**
 * Give a gift card away.
 *
 * No cash is taken and no liability is opened. What is created is
 * merchant-funded promotional value — an expense — and it is classified that
 * way from the moment it exists, so it can never be summed into the money the
 * business owes its customers.
 *
 * The reason category and note are carried into the ledger row, which IS the
 * audit entry. There is no separate audit table to drift out of step with it.
 */
class StoreController extends Controller
{
    use HandlesGiftCardFailures;

    public function __invoke(GrantGiftCardRequest $request)
    {
        $data = $request->validated();

        try {
            $card = GiftCardService::grant(
                amount: (float) $data['amount'],
                // The category is the machine-readable cause, the note is the
                // human one. Both are required; the service refuses an empty
                // reason on its own account.
                reasonCode: strtoupper($data['grant_reason_category']),
                reasonCategory: $data['grant_reason_category'],
                note: $data['grant_note'],
                recipientCustomerId: $data['recipient_customer_id'] ?? null,
                recipientName: $data['recipient_name'] ?? null,
                recipientEmail: $data['recipient_email'] ?? null,
                message: $data['message'] ?? null,
                issuedByStoreId: $data['issued_by_store_id'] ?? null,
                idempotencyKey: $data['idempotency_key'] ?? null,
            );
        } catch (GiftCardException $e) {
            return $this->refusalBack($e);
        }

        // The sender is the granting business identity rather than a
        // purchaser — nobody bought this card. Recorded after issuance
        // because it is presentation, not accounting.
        if (filled($data['sender_name'] ?? null)) {
            $card->forceFill(['sender_name' => $data['sender_name']])->saveQuietly();
        }

        return redirect()
            ->route('admin.gift-cards.show', $card->card_number)
            ->with('success', 'Granted gift card '.$card->card_number.' issued for $'
                .number_format((float) $card->original_value, 2)
                .'. No customer payment was taken.');
    }
}
