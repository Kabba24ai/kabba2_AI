<?php

namespace App\Models\Orders;

use App\Helpers\ModelHelper;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// enums
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Enums\Orders\RefundOperationStatus;

class OrderPayment extends Model
{
    use SoftDeletes;

    // Allow mass assignment for these fields
    protected $fillable = [
        'parent_order_payment_id',
        'unique_id',
        'order_id',
        'payment_method', // e.g., 'COD*', 'Account', 'Card'
        'payment_datetime',
        'refunded_at',
        'transaction_id',
        'gateway_refund_id',
        'card_first_name',
        'card_last_name',
        'card_number',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'cheque_number',
        'payment_note',
        'amount',
        'status', // e.g., 'Pending', 'Paid', 'Account', 'Partial Refund', 'Refunded', 'Failed'
        'refund_amount',
        'tax_refunded',
        'refund_note',
        'refund_calculation_type',
        'refund_operation_status',
        'cc_fee_retained',
        'idempotency_token',
        'payment_response',
        'voided_at',
        'processed_by_id',
        'processed_by_name',
        'processed_reason_code',
        'processed_reason_label',
        'processed_reason_other',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $casts = [
        'payment_method'   => OrderPaymentMethod::class,
        'status'           => OrderPaymentStatus::class,
        'payment_response' => 'json',
        'payment_datetime' => 'datetime',
        'refunded_at'      => 'datetime',
        'voided_at'        => 'datetime',
        'tax_refunded'     => 'decimal:2',
        'cc_fee_retained'  => 'decimal:2',
        'refund_calculation_type' => RefundCalculationType::class,
        'refund_operation_status' => RefundOperationStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-PAY');
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The original settled payment this row refunds/reverses, when this row
     * is itself a refund. Self-referencing on parent_order_payment_id.
     *
     * IMPORTANT (Phase 3B): parent_order_payment_id is retained ONLY as a
     * backward-compatible convenience column for simple, single-value
     * lookups (e.g. quick display, legacy call sites not yet migrated) —
     * it is NOT the source of truth for refund attribution. The
     * `order_payment_refund_allocations` table
     * (see OrderPaymentRefundAllocation / refundAllocations() /
     * receivedRefundAllocations() below) is now the canonical, authoritative
     * record of which original payment(s) fund a given refund, and this
     * column is always derived FROM it — never computed or trusted
     * independently. See PaymentAllocationService::syncParentPointer(),
     * which is the only code allowed to write this column: it sets it from
     * the refund's sole allocation when exactly one exists, and clears it
     * to null once a second allocation exists (a case this single scalar
     * column cannot represent). Do not write parent_order_payment_id
     * directly from new code — go through PaymentAllocationService instead.
     */
    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'parent_order_payment_id');
    }

    /**
     * The refund/void rows that have been recorded against this row as
     * their original payment (inverse of parentPayment()).
     *
     * Same backward-compatible-convenience-only caveat as parentPayment()
     * above applies here — refundAllocations()/receivedRefundAllocations()
     * below are the authoritative relations for refund attribution;
     * parent_order_payment_id-based lookups like this one can miss or
     * misrepresent a refund with more than one allocation, or a legacy
     * refund whose pointer has not yet been backfilled/resolved.
     */
    public function childRefunds(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'parent_order_payment_id');
    }

    /**
     * Allocation rows recorded against THIS row as the refund — i.e. which
     * original payment(s) this refund draws from. Empty for a non-refund
     * row, and for a refund row that predates Phase 3B and has not yet
     * been backfilled.
     */
    public function refundAllocations(): HasMany
    {
        return $this->hasMany(OrderPaymentRefundAllocation::class, 'refund_order_payment_id');
    }

    /**
     * Allocation rows recorded against THIS row as the original payment —
     * i.e. every refund (in whole or in part) that has drawn from this
     * payment. The authoritative source for this payment's remaining
     * refundable balance; see PaymentAllocationService::remainingRefundable().
     */
    public function receivedRefundAllocations(): HasMany
    {
        return $this->hasMany(OrderPaymentRefundAllocation::class, 'original_order_payment_id');
    }

    // Polymorphic relations for created_by and updated_by
    public function createdBy()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', OrderPaymentStatus::Pending);
    }

    public function scopeRefund($query)
    {
        return $query->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund]);
    }

    public function scopePaid($query)
    {
        return $query->where('status', OrderPaymentStatus::Paid);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', OrderPaymentStatus::Failed);
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', OrderPaymentMethod::COD);
    }

    /**
     * Rows that represent real, settled money — Paid, Partial Payment, or
     * any legacy Invoice* status (isSettled() unwinds that fusion). This is
     * the canonical "did this row contribute settled funds" check; prefer
     * it over a hardcoded where('status', 'Paid') so a settled Invoice*
     * row is never silently excluded from a settled-payments total.
     */
    public function scopeSettled($query)
    {
        $settledValues = collect(OrderPaymentStatus::cases())
            ->filter(fn (OrderPaymentStatus $status) => $status->isSettled() || $status === OrderPaymentStatus::PartialPayment)
            ->map(fn (OrderPaymentStatus $status) => $status->value)
            ->all();

        return $query->whereIn('status', $settledValues);
    }

}
