<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Http\Controllers\Controller;
use App\Services\GiftCards\GiftCardWorkspaceQuery;

/**
 * Gift card financial visibility.
 *
 * Deliberately an administrative view, not an export suite. It shows the two
 * sides of the programme next to each other — cash taken at funding, revenue
 * recognised at redemption — because that pairing is the whole accounting
 * model and it is easier to trust when you can see both halves at once.
 *
 * The canonical period reporting still lives in the Sales Summary and the
 * Payment Reconciliation Ledger. This page does not restate their formulas;
 * it shows the programme's position and the events behind it.
 */
class ReportingController extends Controller
{
    public function __construct(private GiftCardWorkspaceQuery $query) {}

    public function __invoke()
    {
        return view('admin.gift_cards.reporting', [
            'kpis' => $this->query->overview(),
            'fundingEvents' => $this->query->recentFundingEvents(),
            'redemptionEvents' => $this->query->recentRedemptionEvents(),
        ]);
    }
}
