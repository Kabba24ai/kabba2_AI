<?php

namespace App\Models\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Goodwill\GoodwillReasonCategory;
use App\Models\Discounts\ProductDiscount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Goodwill\GoodwillException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The authorization and audit record for one Goodwill concession.
 *
 * ── WHAT THIS IS NOT ──────────────────────────────────────────────────────
 *
 * It is not a financial record. It holds no discount amount, no tax, no line
 * allocation and no receipt figure, because the shared pre-tax adjustment
 * engine owns every one of those. Reach them through {@see self::productDiscount()}.
 *
 * ── WHY THERE ARE GUARDS IN A MODEL ───────────────────────────────────────
 *
 * Three invariants are enforced here rather than in a service, because a
 * service can be bypassed by the next caller and this cannot:
 *
 *   1. `reason_category` is DERIVED, never supplied. It is rewritten from
 *      `reason_code` on every save, so the denormalised reporting dimension
 *      cannot drift from the reason it describes.
 *   2. `active_order_id` is DERIVED from `status`, giving the unique index its
 *      meaning. Nothing has to remember to clear it on reversal.
 *   3. The audit fields are IMMUTABLE after the row exists. An audit record
 *      that can be edited afterwards documents the last edit, not the decision.
 *
 * The two-cent residual bound is enforced here for a readable refusal and again
 * by a CHECK constraint in the migration for one that cannot be bypassed.
 */
class OrderGoodwillAdjustment extends Model
{
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REVERSED = 'reversed';

    /** Signed cents of slack permitted between what was collected and the revised total. */
    public const MAX_ROUNDING_RESIDUAL = 0.02;

    /**
     * Set once, at creation, and never again. Everything absent from this list
     * describes the reversal — the only part of the story still unwritten when
     * the row is first persisted.
     */
    private const IMMUTABLE_AFTER_CREATION = [
        'order_id',
        'product_discount_id',
        'reason_code',
        'reason_category',
        'note',
        'approved_by',
        'approved_at',
        'applied_by',
        'accepted_payment_total',
        'rounding_residual',
        'payment_id',
        'settled_payments_snapshot',
        'before_snapshot',
        'after_snapshot',
        'idempotency_key',
        'applied_at',
    ];

    protected $fillable = [
        'order_id', 'product_discount_id', 'reversal_product_discount_id', 'status',
        'reason_code', 'reason_category', 'note',
        'approved_by', 'approved_at', 'applied_by',
        'accepted_payment_total', 'rounding_residual',
        'payment_id', 'settled_payments_snapshot',
        'before_snapshot', 'after_snapshot',
        'idempotency_key', 'applied_at',
        'reversed_at', 'reversed_by', 'reversal_reason',
    ];

    protected $casts = [
        'reason_code' => GoodwillReason::class,
        'reason_category' => GoodwillReasonCategory::class,
        'accepted_payment_total' => 'decimal:2',
        'rounding_residual' => 'decimal:2',
        'settled_payments_snapshot' => 'array',
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $adjustment): void {
            $adjustment->deriveCategory();
            $adjustment->deriveActiveOrderId();
            $adjustment->assertNoteAccompaniesUncategorisedReason();
            $adjustment->assertResidualWithinBound();
        });

        static::updating(function (self $adjustment): void {
            $changed = array_intersect(
                array_keys($adjustment->getDirty()),
                self::IMMUTABLE_AFTER_CREATION,
            );

            if ($changed !== []) {
                throw new GoodwillException(
                    'Goodwill adjustment #'.$adjustment->id.' is an audit record: '
                    .implode(', ', $changed).' cannot be modified after it is written. '
                    .'Reverse the adjustment and apply a new one instead.'
                );
            }

            $adjustment->assertNotBeingResurrected();
        });
    }

    // ── Derived state ──────────────────────────────────────────────────────

    /** The reporting dimension always matches the reason it was derived from. */
    private function deriveCategory(): void
    {
        if ($this->reason_code instanceof GoodwillReason) {
            $this->attributes['reason_category'] = $this->reason_code->category()->value;
        }
    }

    /**
     * Populate the column the unique index constrains: the order id while this
     * adjustment is live, NULL once it is not. NULLs do not collide in a MySQL
     * unique index, so any number of reversed rows may coexist with at most one
     * applied row per order.
     */
    private function deriveActiveOrderId(): void
    {
        $this->attributes['active_order_id'] = $this->status === self::STATUS_APPLIED
            ? $this->order_id
            : null;
    }

    // ── Invariants ─────────────────────────────────────────────────────────

    private function assertNoteAccompaniesUncategorisedReason(): void
    {
        if (! $this->reason_code instanceof GoodwillReason || ! $this->reason_code->requiresNote()) {
            return;
        }

        if (trim((string) $this->note) === '') {
            throw new GoodwillException(
                'A Goodwill adjustment recorded as "'.$this->reason_code->label().'" requires a written '
                .'explanation. Without one there is no record of why revenue was reduced.'
            );
        }
    }

    /**
     * A reversed decision is final. It may never return to `applied`.
     *
     * Without this, flipping `status` back would silently re-arm
     * `active_order_id` and reinstate a concession the business had withdrawn —
     * with the original `applied_at`, the original approver, and no record that
     * it had ever been reversed. Re-granting is a NEW decision, needing its own
     * authorization, its own snapshot and its own row; the reversal stays in the
     * history beside it.
     */
    private function assertNotBeingResurrected(): void
    {
        $wasReversed = ($this->getOriginal('status') ?? null) === self::STATUS_REVERSED;

        if ($wasReversed && $this->status === self::STATUS_APPLIED) {
            throw new GoodwillException(
                'Goodwill adjustment #'.$this->id.' has been reversed and cannot be reinstated. '
                .'Apply a new Goodwill adjustment instead — re-granting is a new decision and '
                .'needs its own authorization and audit record.'
            );
        }
    }

    /**
     * A residual larger than two cents is not rounding.
     *
     * The grand total steps down by one to three cents per cent of concession,
     * so the gap between the collected amount and the closest reachable total
     * can never legitimately exceed two. A larger value means the concession
     * was sized against different figures than the ones it was written with —
     * stale state, or a calculation that did not converge — and it must not be
     * quietly absorbed into the amount.
     */
    private function assertResidualWithinBound(): void
    {
        $residual = round(abs((float) $this->rounding_residual), 2);

        if ($residual > self::MAX_ROUNDING_RESIDUAL) {
            throw new GoodwillException(
                'Goodwill rounding residual of '.number_format((float) $this->rounding_residual, 2)
                .' exceeds the permitted '.number_format(self::MAX_ROUNDING_RESIDUAL, 2)
                .'. A gap this large is not rounding — recalculate the adjustment against current '
                .'order and payment figures. Nothing was written.'
            );
        }
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** The financial adjustment — the source of truth for every amount. */
    public function productDiscount()
    {
        return $this->belongsTo(ProductDiscount::class, 'product_discount_id');
    }

    public function reversalProductDiscount()
    {
        return $this->belongsTo(ProductDiscount::class, 'reversal_product_discount_id');
    }

    /** The manager who authorized the concession. */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** The operator who executed it, when different from the approver. */
    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** Convenience pointer only — null whenever more than one settled payment existed. */
    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'payment_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeApplied(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /** Grouping on the stored dimension — no label parsing, no re-derivation. */
    public function scopeInCategory(Builder $query, GoodwillReasonCategory $category): Builder
    {
        return $query->where('reason_category', $category->value);
    }

    // ── Reads ──────────────────────────────────────────────────────────────

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * The concession amount, read from the engine's record rather than held
     * here. Null only if the linked discount has somehow gone.
     */
    public function concessionAmount(): ?float
    {
        $discount = $this->productDiscount;

        return $discount ? (float) $discount->calculated_discount_amount : null;
    }

    /**
     * The at-most-one live adjustment for an order, or null.
     *
     * Reads the same column the unique index constrains, so it can never
     * disagree with what the database would permit.
     */
    public static function activeFor(int $orderId): ?self
    {
        return static::where('active_order_id', $orderId)->first();
    }
}
