<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Http\Controllers\Controller;
use App\Services\GiftCards\GiftCardWorkspaceQuery;

/**
 * The gift card programme at a glance.
 *
 * Every figure is read from the ledger through {@see GiftCardWorkspaceQuery}.
 * Nothing on this page is estimated, sampled or placeholder — an install with
 * no cards renders zeros and says so, which is a truthful answer, whereas a
 * demo number on a financial screen is not.
 */
class OverviewController extends Controller
{
    public function __construct(private GiftCardWorkspaceQuery $query) {}

    public function __invoke()
    {
        return view('admin.gift_cards.overview', [
            'kpis' => $this->query->overview(),
            'recentCards' => $this->query->recentCards(),
            'recentTransactions' => $this->query->recentTransactions(),
        ]);
    }
}
