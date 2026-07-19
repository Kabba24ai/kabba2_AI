<?php

namespace App\Models\Orders;

use App\Enums\Billing\BillingChargeRefundStatus;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds.
 *
 * One row per successful (or attempted) refund against a single
 * originating BillingCharge. The authoritative source of cumulative
 * refunded value for a Fuel or Damage charge — see
 * App\Services\Orders\BillingChargeRefundService, the only class
 * authorized to create or resolve these rows, mirroring the "who decides
 * vs. who writes" separation PaymentAllocationService already maintains
 * for order_payment_refund_allocations.
 *
 * This model has no business logic of its own beyond relationships,
 * casts, and the consumed() scope.
 */
class BillingChargeRefund extends Model
{
    protected $table = 'billing_charge_refunds';

    protected $fillable = [
        'billing_charge_id',
        'customer_account_id',
        'responsible_person_id',
        'base_amount',
        'tax_amount',
        'total_amount',
        'status',
        'failure_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'base_amount'  => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status'       => BillingChargeRefundStatus::class,
    ];

    public function billingCharge(): BelongsTo
    {
        return $this->belongsTo(BillingCharge::class, 'billing_charge_id');
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /** Allocations that have actually, successfully consumed refundable balance — Allocated only (Pending/Failed never do, per this mission's explicit requirement). */
    public function scopeConsumed($query)
    {
        return $query->where('status', BillingChargeRefundStatus::Allocated->value);
    }
}
