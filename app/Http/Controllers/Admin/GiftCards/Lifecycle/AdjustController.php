<?php

namespace App\Http\Controllers\Admin\GiftCards\Lifecycle;

use App\Http\Controllers\Admin\GiftCards\Concerns\HandlesGiftCardFailures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\AdjustGiftCardRequest;
use App\Models\GiftCards\GiftCard;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardService;

class AdjustController extends Controller
{
    use HandlesGiftCardFailures;

    public function __invoke(AdjustGiftCardRequest $request, string $cardNumber)
    {
        $card = GiftCard::where('card_number', $cardNumber)->firstOrFail();
        $data = $request->validated();

        $increase = $data['direction'] === 'increase';

        try {
            $increase
                ? GiftCardService::adjustIncrease($card, (float) $data['amount'], $data['reason'], $data['note'] ?? null)
                : GiftCardService::adjustDecrease($card, (float) $data['amount'], $data['reason'], $data['note'] ?? null);
        } catch (GiftCardException $e) {
            return $this->refusalBack($e);
        }

        return redirect()
            ->route('admin.gift-cards.show', $card->card_number)
            ->with('success', sprintf(
                'Balance adjusted: $%s %s. The movement is recorded in the ledger.',
                number_format((float) $data['amount'], 2),
                $increase ? 'added' : 'removed',
            ));
    }
}
