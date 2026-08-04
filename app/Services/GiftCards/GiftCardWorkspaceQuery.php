<?php

namespace App\Services\GiftCards;

use App\Enums\GiftCards\GiftCardIssuanceClass;
use App\Enums\GiftCards\GiftCardStatus;
use App\Enums\GiftCards\GiftCardTransactionType;
use App\Models\GiftCards\GiftCard;
use App\Models\GiftCards\GiftCardTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only figures for the gift card workspace.
 *
 * ── EVERY NUMBER HERE COMES FROM THE LEDGER ───────────────────────────────
 *
 * `gift_cards.cached_balance` is a projection maintained by the service. It
 * is fine for a list column, but it is not what a total is summed from: a
 * KPI built on a cache is a KPI that can quietly disagree with the accounts.
 * Balances and movements are therefore summed from `gift_card_transactions`,
 * the canonical record.
 *
 * ── PURCHASED AND GRANTED ARE NEVER ADDED TOGETHER ────────────────────────
 *
 * A purchased card is money the business OWES — real cash was taken and
 * goods have not yet been supplied. A granted card is money the business
 * GAVE AWAY — no cash ever existed. Summing them produces a number that is
 * neither a liability nor an expense, so every figure below is reported by
 * class and the two are only ever shown side by side.
 *
 * This class does not duplicate the period reporting in SalesReportEngineV2
 * or the reconciliation streams in PaymentReconciliationLedger. It answers
 * "what does the gift card programme look like right now", which is a
 * position, not a flow.
 */
class GiftCardWorkspaceQuery
{
    /**
     * Programme position as at now.
     *
     * @return array<string, float|int>
     */
    public function overview(): array
    {
        // One grouped pass over the ledger, joined to its card, rather than a
        // query per figure. Signed amounts mean issuance is positive and
        // redemption negative, so a class total IS the outstanding balance.
        $byClassAndType = DB::table('gift_card_transactions as gct')
            ->join('gift_cards as gc', 'gc.id', '=', 'gct.gift_card_id')
            ->whereNull('gc.deleted_at')
            ->groupBy('gc.issuance_class', 'gct.type')
            ->select([
                'gc.issuance_class',
                'gct.type',
                DB::raw('SUM(gct.amount) as total'),
            ])
            ->get();

        $sum = function (string $class, array $types) use ($byClassAndType): float {
            return round((float) $byClassAndType
                ->where('issuance_class', $class)
                ->whereIn('type', $types)
                ->sum('total'), 2);
        };

        $purchased = GiftCardIssuanceClass::Purchased->value;
        $granted = GiftCardIssuanceClass::Granted->value;

        $issuance = [GiftCardTransactionType::IssuancePurchased->value, GiftCardTransactionType::IssuanceGranted->value];
        $redemption = [GiftCardTransactionType::Redemption->value];

        // Outstanding balance = every signed movement on that class of card.
        $outstanding = fn (string $class): float => round(
            (float) $byClassAndType->where('issuance_class', $class)->sum('total'), 2
        );

        // Redemption rows are negative in the ledger; reported as a positive
        // "value redeemed" figure because that is how it reads to a human.
        return [
            'active_cards' => GiftCard::query()
                ->whereIn('status', [
                    GiftCardStatus::Active->value,
                    GiftCardStatus::PartiallyRedeemed->value,
                ])->count(),
            'total_cards' => GiftCard::query()->count(),

            'purchased_outstanding' => $outstanding($purchased),
            'granted_outstanding' => $outstanding($granted),

            'purchased_issued' => $sum($purchased, $issuance),
            'purchased_redeemed' => abs($sum($purchased, $redemption)),

            'granted_issued' => $sum($granted, $issuance),
            'granted_redeemed' => abs($sum($granted, $redemption)),

            // Real external cash: purchased funding only, net of voids. A
            // granted card contributes nothing here because nobody paid.
            'funding_cash' => round((float) GiftCardTransaction::query()
                ->where('type', GiftCardTransactionType::IssuancePurchased->value)
                ->whereNull('funding_voided_at')
                ->sum('funding_cash_amount'), 2),
        ];
    }

    /** Most recently issued cards, for the overview. */
    public function recentCards(int $limit = 8): Collection
    {
        return GiftCard::query()
            ->with(['recipient', 'purchaser', 'issuedByStore'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** Most recent ledger movements across all cards, for the overview. */
    public function recentTransactions(int $limit = 10): Collection
    {
        return GiftCardTransaction::query()
            ->with(['giftCard', 'order'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Funding events — the external cash side of the programme, and the rows
     * that reconcile against a processor settlement (Stream E).
     */
    public function recentFundingEvents(int $limit = 15): Collection
    {
        return GiftCardTransaction::query()
            ->with('giftCard')
            ->where('type', GiftCardTransactionType::IssuancePurchased->value)
            ->whereNotNull('funding_cash_amount')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** Redemption events — revenue recognised, no external cash. */
    public function recentRedemptionEvents(int $limit = 15): Collection
    {
        return GiftCardTransaction::query()
            ->with(['giftCard', 'order', 'orderPayment'])
            ->where('type', GiftCardTransactionType::Redemption->value)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The card list, filtered and searched.
     *
     * @param  array{class?:string|null,status?:string|null,search?:string|null}  $filters
     */
    public function cards(array $filters, int $perPage = 25)
    {
        $query = GiftCard::query()
            ->with(['recipient', 'purchaser', 'issuedByStore'])
            ->withMax('transactions', 'created_at');

        if (filled($filters['class'] ?? null)) {
            $query->where('issuance_class', $filters['class']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['search'] ?? null)) {
            $term = trim((string) $filters['search']);
            $like = '%'.$term.'%';

            // Card number, the people named ON the card, and the purchasing
            // customer — the four things someone actually has to hand when
            // they are looking for a card.
            $query->where(function ($q) use ($like) {
                $q->where('card_number', 'like', $like)
                    ->orWhere('recipient_name', 'like', $like)
                    ->orWhere('sender_name', 'like', $like)
                    ->orWhere('recipient_email', 'like', $like)
                    ->orWhereHas('purchaser', function ($c) use ($like) {
                        $c->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
