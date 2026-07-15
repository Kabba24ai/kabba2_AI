<?php

namespace App\Models\Orders;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * One row per (refund, original payment) attribution. The authoritative
 * source of which original payment funded a given refund — see
 * App\Services\Orders\PaymentAllocationService, the only class authorized
 * to create or resolve these rows, the same "who decides vs. who writes"
 * separation CustomerCreditService already maintains for customer_credits.
 *
 * This model has no business logic of its own beyond relationships and
 * casts.
 */
class OrderPaymentRefundAllocation extends Model
{
    protected $table = 'order_payment_refund_allocations';

    protected $fillable = [
        'refund_order_payment_id',
        'original_order_payment_id',
        'allocated_amount',
        'allocated_base_amount',
        'allocated_tax_amount',
        'processing_fee_retained',
        'gateway_transaction_id',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'allocated_base_amount' => 'decimal:2',
        'allocated_tax_amount' => 'decimal:2',
        'processing_fee_retained' => 'decimal:2',
        'status' => OrderPaymentRefundAllocationStatus::class,
    ];

    public function refundPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'refund_order_payment_id');
    }

    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'original_order_payment_id');
    }

    /** Allocations that still tie up refundable balance (pending or allocated). */
    public function scopeReserving($query)
    {
        return $query->whereIn('status', [
            OrderPaymentRefundAllocationStatus::Pending->value,
            OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
    }
}
