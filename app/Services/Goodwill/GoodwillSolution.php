<?php

namespace App\Services\Goodwill;

/**
 * What a Goodwill concession of a particular size would do to an order.
 *
 * Every figure is integer cents and came from the shared engine's own
 * `computeTotals()` — the same function the writer persists from. Nothing here
 * is derived independently, so a preview cannot show an operator a number the
 * writer would then contradict.
 */
final class GoodwillSolution
{
    /**
     * @param  int  $concessionCents  the pre-tax concession to grant
     * @param  int  $acceptedPaymentCents  settled payments this was sized against
     * @param  int  $residualCents  accepted − revised grand total; 0 on an exact close
     * @param  array<string,int>  $totals  the engine's full breakdown at this concession
     * @param  array<string,int>  $totalsBefore  the same breakdown at the current concession
     */
    public function __construct(
        public readonly int $concessionCents,
        public readonly int $acceptedPaymentCents,
        public readonly int $residualCents,
        public readonly array $totals,
        public readonly array $totalsBefore,
    ) {
    }

    public function concession(): float
    {
        return (float) ($this->concessionCents / 100);
    }

    public function residual(): float
    {
        return (float) ($this->residualCents / 100);
    }

    public function revisedGrandTotal(): float
    {
        return (float) ($this->totals['grand_total'] / 100);
    }

    public function acceptedPaymentTotal(): float
    {
        return (float) ($this->acceptedPaymentCents / 100);
    }

    /** Closes the balance to the cent, with nothing left over. */
    public function isExactClose(): bool
    {
        return $this->residualCents === 0;
    }

    /**
     * The operator-facing breakdown: what the concession is, and every figure
     * that moves because of it. Dollars, because this is display.
     *
     * @return array<string,float>
     */
    public function toDisplayArray(): array
    {
        // Every value is cast, not merely divided: PHP's `/` returns an int
        // when the division is exact, so $20,000c would arrive as int(200) and
        // a display layer expecting money would format it differently from the
        // cent-bearing figures beside it.
        $dollars = static fn (int $cents): float => (float) ($cents / 100);

        return [
            'amount_collected' => $this->acceptedPaymentTotal(),
            'remaining_balance' => $dollars($this->totalsBefore['grand_total'] - $this->acceptedPaymentCents),
            'goodwill_amount' => $this->concession(),
            'gross_subtotal' => $dollars($this->totals['subtotal']),
            'total_pretax_adjustments' => $dollars($this->totals['discount']),
            'discounted_product_value' => $dollars($this->totals['remaining']),
            'sales_tax' => $dollars($this->totals['tax']),
            'special_tax' => $dollars($this->totals['special_tax']),
            'added_fees' => $dollars($this->totals['added_fees']),
            'revised_grand_total' => $this->revisedGrandTotal(),
            'rounding_residual' => $this->residual(),
        ];
    }
}
