<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Enums\GiftCards\GiftCardTransactionType;
use App\Http\Controllers\Controller;
use App\Models\GiftCards\GiftCardTransaction;
use Illuminate\Http\Request;

/**
 * Every movement across every card, newest first.
 *
 * The per-card ledger on the detail page answers "what happened to this
 * card". This answers "what happened today" — the view someone takes when
 * they are reconciling rather than investigating.
 */
class TransactionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = $request->input('type') ?: null;

        $transactions = GiftCardTransaction::query()
            ->with(['giftCard', 'order', 'orderPayment', 'createdBy'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.gift_cards.transactions', [
            'transactions' => $transactions,
            'types' => GiftCardTransactionType::cases(),
            'filters' => ['type' => $type],
        ]);
    }
}
