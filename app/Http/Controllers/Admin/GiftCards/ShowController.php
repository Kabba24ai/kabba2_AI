<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Http\Controllers\Controller;
use App\Models\GiftCards\GiftCard;
use App\Services\GiftCards\GiftCardPermissions;

/**
 * The card detail page — the centre of the workspace.
 *
 * Shows the approved artwork populated with this card's live values, its
 * position, and the full ledger. The ledger is shown oldest-first: it is a
 * story of what happened to the card, and a story reads forwards.
 */
class ShowController extends Controller
{
    public function __invoke(string $cardNumber)
    {
        $card = GiftCard::query()
            ->with([
                'purchaser',
                'recipient',
                'issuedByStore',
                'replacedBy',
                'transactions' => fn ($q) => $q->with(['order', 'orderPayment', 'createdBy'])->oldest('id'),
            ])
            ->where('card_number', $cardNumber)
            ->firstOrFail();

        $user = auth()->user();

        return view('admin.gift_cards.show', [
            'card' => $card,

            // The ledger is canonical; the column is a cache. If they have
            // drifted the page says so rather than quietly showing whichever
            // one it happened to read.
            'balance' => $card->availableBalance(),
            'cacheIsStale' => $card->cacheIsStale(),

            // Which buttons to render. The service asserts these again at its
            // own boundary — hiding a button is a courtesy, not a control.
            'can' => [
                'suspend' => GiftCardPermissions::canSuspend($user),
                'cancel' => GiftCardPermissions::canCancel($user),
                'replace' => GiftCardPermissions::canReplace($user),
                'adjust' => GiftCardPermissions::canAdjust($user),
            ],
        ]);
    }
}
