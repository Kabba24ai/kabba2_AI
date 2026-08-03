<?php

namespace App\Services\Orders;

use App\Enums\Discounts\DiscountType;
use App\Models\Discounts\ProductDiscount;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Orders\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Which pre-tax adjustments an order actually carries, and what each one was.
 *
 * NOT TO BE CONFUSED WITH {@see OrderFinancialActivity}, which answers a
 * different question entirely — whether an order is still OPERATIONALLY ACTIVE
 * for scheduling, dispatch and queue-line purposes. That one decides visibility
 * of work; this one describes money to a reader. Neither calls the other.
 *
 * ── WHY IT EXISTS ─────────────────────────────────────────────────────────
 *
 * The order summary showed a single cumulative "Pre-Tax Discounts −$66.97".
 * Accurate, and it explained nothing: it could not say whether the concession
 * was Store Credit, Goodwill, or both. That attribution is NOT derivable from
 * `orders.pretax_discount_total`, which is one number. It lives only on the
 * `product_discounts` rows.
 *
 * ── READ-ONLY. NO SECOND LEDGER. ──────────────────────────────────────────
 *
 * Nothing is stored and no amount is duplicated. Amounts come from
 * `product_discounts.calculated_discount_amount`, Goodwill's authorization
 * detail from `order_goodwill_adjustments`, payments from `order_payments`,
 * and the cumulative total from the order's own column.
 */
final class OrderFinancialHistory
{
    /** Safety fallback only — not a supported state. See activeAdjustmentLines(). */
    public const UNATTRIBUTED_LABEL = 'Other Pre-Tax Adjustment';

    private function __construct(
        private readonly Order $order,
        private readonly Collection $discounts,
        private readonly Collection $goodwill,
    ) {
    }

    public static function for(Order $order): self
    {
        $discounts = ProductDiscount::query()
            ->where('target_type', 'order')
            ->where('target_id', $order->id)
            ->with('appliedBy')
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();

        $goodwill = OrderGoodwillAdjustment::query()
            ->where('order_id', $order->id)
            ->with(['approvedBy', 'reversedBy'])
            ->get()
            ->keyBy('product_discount_id');

        return new self($order, $discounts, $goodwill);
    }

    /**
     * One line per active adjustment TYPE, for the order summary.
     *
     * Several adjustments of the same type sum into one line — the combined
     * figure is exact, and the individual events are listed in the activity
     * modal. Different types never merge: that is the information the old
     * generic line destroyed.
     *
     * The lines always sum to `orders.pretax_discount_total`. If the tracked
     * rows do not account for it exactly the difference is emitted under
     * {@see self::UNATTRIBUTED_LABEL} and logged — a safety net so the page can
     * never display a set of lines that disagrees with the order's own total,
     * not a workflow anything is expected to produce.
     *
     * @return list<array{key: string, label: string, amount: float, attributed: bool}>
     */
    public function activeAdjustmentLines(): array
    {
        $total = $this->cents($this->order->pretax_discount_total);

        if ($total <= 0) {
            return [];
        }

        $lines = [];
        $attributed = 0;

        foreach ($this->activeDiscounts()->groupBy(fn ($d) => $this->typeValue($d)) as $typeValue => $rows) {
            $type = DiscountType::tryFrom((string) $typeValue);

            if ($type === null) {
                continue;
            }

            $amount = $rows->sum(fn ($d) => $this->cents($d->calculated_discount_amount));
            $attributed += $amount;

            $lines[] = [
                'key' => $type->value,
                'label' => $type->receiptLabel(),
                // Cast, not merely divided: PHP's `/` yields an int when the
                // division is exact, so $5,000c would arrive as int(50) while
                // $5,025c arrived as float — and a display layer would format
                // the two differently.
                'amount' => (float) ($amount / 100),
                'attributed' => true,
            ];
        }

        if ($attributed === $total) {
            return $lines;
        }

        Log::warning('Order '.$this->order->id.': active pre-tax adjustment rows total '
            .number_format($attributed / 100, 2).' but orders.pretax_discount_total is '
            .number_format($total / 100, 2).'. Displaying the difference as "'
            .self::UNATTRIBUTED_LABEL.'" so the summary still reconciles.');

        // Tracked rows claim more than the order does — the record contradicts
        // itself, so no split derived from it can be trusted.
        if ($attributed > $total) {
            return [$this->unattributedLine($total)];
        }

        $lines[] = $this->unattributedLine($total - $attributed);

        return $lines;
    }

    /** Real money received. Adjustments are never here — they are not tender. */
    public function paymentsReceived(): Collection
    {
        return $this->order->payments()->settled()->with('createdBy')->orderBy('id')->get();
    }

    /**
     * One entry per adjustment event, active and reversed, oldest first.
     *
     * A reversed adjustment stays visible and is marked `Reversed`. Removing it
     * would hide that a concession was granted and then withdrawn — exactly the
     * sequence an auditor most needs to see.
     *
     * @return list<array<string,mixed>>
     */
    public function pretaxAdjustments(): array
    {
        return $this->originalDiscounts()
            ->map(fn (ProductDiscount $d) => $this->describe($d))
            ->all();
    }

    // ── internals ──────────────────────────────────────────────────────────

    /**
     * One adjustment, described.
     *
     * The first block is the basic financial fact — visible to anyone who can
     * see the order, and already printed on the customer's receipt. The second
     * is Goodwill's internal authorization detail, null for every other type,
     * and rendered only behind a direct Spatie permission check.
     *
     * @return array<string,mixed>
     */
    private function describe(ProductDiscount $discount): array
    {
        $type = $discount->discount_type instanceof DiscountType
            ? $discount->discount_type
            : DiscountType::tryFrom((string) $discount->discount_type);

        $audit = $this->goodwill->get($discount->id);
        $reversed = $discount->isReversed();

        return [
            'type' => $type,
            'label' => $type?->receiptLabel() ?? self::UNATTRIBUTED_LABEL,
            'amount' => round((float) $discount->calculated_discount_amount, 2),
            'at' => $discount->applied_at,
            'status' => $reversed ? 'Reversed' : 'Applied',
            'reversed' => $reversed,

            // Goodwill only — permission-gated at the view.
            'reason' => $audit?->reason_code?->label(),
            'reason_category' => $audit?->reason_category?->label(),
            'approved_by' => $audit?->approvedBy?->full_name,
            'accepted_payment_total' => $audit ? round((float) $audit->accepted_payment_total, 2) : null,
            'rounding_residual' => $audit ? round((float) $audit->rounding_residual, 2) : null,
            'note' => $audit?->note,
            'reversal_reason' => $audit?->reversal_reason,
            'reversed_by' => $audit?->reversedBy?->full_name,
            'has_internal_detail' => $audit !== null,
        ];
    }

    /** @return array{key: string, label: string, amount: float, attributed: bool} */
    private function unattributedLine(int $cents): array
    {
        return [
            'key' => 'unattributed',
            'label' => self::UNATTRIBUTED_LABEL,
            'amount' => (float) ($cents / 100),
            'attributed' => false,
        ];
    }

    /**
     * The adjustments themselves — not the compensating rows a reversal
     * appends, which would otherwise read as a second, phantom concession.
     */
    private function originalDiscounts(): Collection
    {
        return $this->discounts
            ->filter(fn (ProductDiscount $d) => ! isset($d->metadata['reversal_of']))
            ->values();
    }

    private function activeDiscounts(): Collection
    {
        return $this->originalDiscounts()
            ->filter(fn (ProductDiscount $d) => $d->status === ProductDiscount::STATUS_APPLIED)
            ->values();
    }

    private function typeValue(ProductDiscount $discount): string
    {
        return $discount->discount_type instanceof DiscountType
            ? $discount->discount_type->value
            : (string) $discount->discount_type;
    }

    private function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
