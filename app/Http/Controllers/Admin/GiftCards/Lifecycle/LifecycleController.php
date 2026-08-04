<?php

namespace App\Http\Controllers\Admin\GiftCards\Lifecycle;

use App\Http\Controllers\Admin\GiftCards\Concerns\HandlesGiftCardFailures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\GiftCardLifecycleRequest;
use App\Models\GiftCards\GiftCard;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardService;

/**
 * Suspend, reinstate, cancel and replace.
 *
 * ── EVERY ACTION GOES THROUGH THE SERVICE ─────────────────────────────────
 *
 * There is no `$card->update(['status' => ...])` anywhere in this class, and
 * there must never be. Cancelling writes the remaining balance off as an
 * explicit negative ledger row; replacing writes two rows so value visibly
 * leaves one card and arrives at another. A direct status update would change
 * what a card is worth while leaving the ledger — the canonical record —
 * saying otherwise.
 *
 * Authorization is asserted inside the service for the same reason: a
 * controller guard protects this screen, a service guard protects the
 * operation.
 */
class LifecycleController extends Controller
{
    use HandlesGiftCardFailures;

    public function suspend(GiftCardLifecycleRequest $request, string $cardNumber)
    {
        return $this->perform(
            $cardNumber,
            fn (GiftCard $card) => GiftCardService::suspend($card, $request->validated()['reason']),
            'Gift card suspended. Its value is untouched and it can be reinstated.',
        );
    }

    public function reinstate(GiftCardLifecycleRequest $request, string $cardNumber)
    {
        return $this->perform(
            $cardNumber,
            fn (GiftCard $card) => GiftCardService::reinstate($card, $request->validated()['reason']),
            'Gift card reinstated and back in circulation.',
        );
    }

    public function cancel(GiftCardLifecycleRequest $request, string $cardNumber)
    {
        return $this->perform(
            $cardNumber,
            fn (GiftCard $card) => GiftCardService::cancel($card, $request->validated()['reason']),
            'Gift card cancelled. Any remaining value was written off in the ledger.',
        );
    }

    /**
     * Replacement redirects to the NEW card — that is the one the operator
     * now has to hand to the customer, so it is the one they should be
     * looking at.
     */
    public function replace(GiftCardLifecycleRequest $request, string $cardNumber)
    {
        $card = $this->find($cardNumber);

        try {
            $replacement = GiftCardService::replace($card, $request->validated()['reason']);
        } catch (GiftCardException $e) {
            return $this->refusalBack($e);
        }

        return redirect()
            ->route('admin.gift-cards.show', $replacement->card_number)
            ->with('success', 'Replaced '.$card->card_number.' with '.$replacement->card_number
                .'. The original is retired and can no longer be used.');
    }

    private function perform(string $cardNumber, callable $action, string $message)
    {
        $card = $this->find($cardNumber);

        try {
            $action($card);
        } catch (GiftCardException $e) {
            return $this->refusalBack($e);
        }

        return redirect()
            ->route('admin.gift-cards.show', $card->card_number)
            ->with('success', $message);
    }

    private function find(string $cardNumber): GiftCard
    {
        return GiftCard::where('card_number', $cardNumber)->firstOrFail();
    }
}
