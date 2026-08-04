<?php

namespace App\Http\Controllers\Admin\GiftCards\Purchased;

use App\Enums\Orders\OrderPaymentMethod;
use App\Http\Controllers\Admin\GiftCards\Concerns\HandlesGiftCardFailures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\PurchaseGiftCardRequest;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardService;

/**
 * Sell a gift card.
 *
 * ── NO FAKE PRODUCT, NO FAKE ORDER ────────────────────────────────────────
 *
 * This creates a standalone funding record and nothing else. There is no
 * catalogue item, no order, no order_product. That is what keeps a card sale
 * invisible to CollectedRevenueQuery — which requires order lines — so it can
 * never be mistaken for taxable merchandise revenue. The exclusion is a
 * property of the shape of the data, not a filter someone has to remember.
 *
 * All this controller does is unwrap the request and hand it to the service.
 * Every rule that matters — the funding method cannot be a gift card, the
 * amount must be positive, the caller must hold SELL — lives in the service,
 * where a future queued job or API endpoint meets it too.
 */
class StoreController extends Controller
{
    use HandlesGiftCardFailures;

    public function __invoke(PurchaseGiftCardRequest $request)
    {
        $data = $request->validated();

        try {
            $card = GiftCardService::purchase(
                amount: (float) $data['amount'],
                fundingMethod: OrderPaymentMethod::from($data['funding_method']),
                purchaserCustomerId: $data['purchaser_customer_id'] ?? null,
                recipientName: $data['recipient_name'] ?? null,
                senderName: $data['sender_name'] ?? null,
                recipientEmail: $data['recipient_email'] ?? null,
                message: $data['message'] ?? null,
                issuedByStoreId: $data['issued_by_store_id'] ?? null,
                fundingTransactionId: $data['funding_transaction_id'] ?? null,
                fundedAt: $data['funded_at'] ?? null,
                idempotencyKey: $data['idempotency_key'] ?? null,
            );
        } catch (GiftCardException $e) {
            return $this->refusalBack($e);
        }

        if (filled($data['pin'] ?? null)) {
            // Hashed by the model's cast — the typed value is never stored.
            $card->forceFill(['pin_hash' => bcrypt($data['pin'])])->saveQuietly();
        }

        return redirect()
            ->route('admin.gift-cards.show', $card->card_number)
            ->with('success', 'Gift card '.$card->card_number.' issued for $'
                .number_format((float) $card->original_value, 2).'.');
    }
}
