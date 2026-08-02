<?php

namespace App\Models\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The audit record of one Goodwill Adjustment (FD-002, Truth Table type 17).
 *
 * The ORDER's columns are the current authoritative financial state; this row
 * is the history. It preserves the pre-adjustment snapshot so what management
 * approved can always be reconstructed, and the per-line allocations so a
 * reversal restores exactly what was changed rather than recomputing it.
 *
 * A reversal is recorded on this row (reversed_at / reversed_by /
 * reversal_reason). Rows are never deleted and the original snapshot columns
 * are never rewritten.
 *
 * There is deliberately no "amount paid after" column. Goodwill moves no
 * money — it reduces the pre-tax product cost so the revised grand total
 * equals what was already collected — so a before/after pair on the money
 * would always hold the same number and would read as evidence of a payment
 * that never happened. `payments_accepted` records the single relevant fact:
 * the cumulative settled payments management accepted as payment in full.
 * The thing that genuinely does change is the DERIVED payment status, which
 * is what payment_status_before / payment_status_after record.
 */
class OrderGoodwillAdjustment extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'goodwill_amount',
        'reason_code',
        'reason_note',
        'approved_by',
        'performed_by',
        'original_subtotal',
        'original_tax',
        'original_special_tax',
        'original_grand_total',
        'revised_subtotal',
        'revised_tax',
        'revised_special_tax',
        'revised_grand_total',
        'line_allocations',
        'basis_snapshot',
        'payments_accepted',
        'payment_status_before',
        'payment_status_after',
        'order_payment_id',
        'superseded_receipt_id',
        'idempotency_token',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'reason_code'          => GoodwillReasonCode::class,
        'goodwill_amount'      => 'decimal:2',
        'original_subtotal'    => 'decimal:2',
        'original_tax'         => 'decimal:2',
        'original_special_tax' => 'decimal:2',
        'original_grand_total' => 'decimal:2',
        'revised_subtotal'     => 'decimal:2',
        'revised_tax'          => 'decimal:2',
        'revised_special_tax'  => 'decimal:2',
        'revised_grand_total'  => 'decimal:2',
        'payments_accepted'    => 'decimal:2',
        'line_allocations'     => 'array',
        'basis_snapshot'       => 'array',
        'reversed_at'          => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** The authenticated manager who authorised reducing revenue. */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** The employee who physically processed the payment. */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** The real tender recorded alongside this adjustment, if any. Goodwill itself is never a payment. */
    public function orderPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    /** Active = applied and not reversed. At most one may exist per order. */
    public function scopeActive($query)
    {
        return $query->whereNull('reversed_at');
    }
}
