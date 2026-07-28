<?php

namespace App\Models\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only product-discount event. See the migration for the contract.
 * Reversal is a compensating row (status='reversed' on the original, a new row
 * linked via reversed_by_discount_id); rows are never deleted or overwritten.
 */
class ProductDiscount extends Model
{
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'discount_type', 'calculation_type', 'source_amount', 'percentage',
        'calculated_discount_amount', 'target_type', 'target_id', 'customer_id',
        'original_product_value', 'discounted_product_value',
        'taxable_value_before', 'taxable_value_after', 'tax_before', 'tax_after',
        'store_credit_redemption_id', 'applied_by', 'applied_at', 'source_interface',
        'reason', 'idempotency_key', 'status', 'reversed_by_discount_id', 'metadata',
    ];

    protected $casts = [
        'discount_type' => DiscountType::class,
        'calculation_type' => DiscountCalculationType::class,
        'target_type' => DiscountTargetType::class,
        'source_amount' => 'decimal:2',
        'percentage' => 'decimal:4',
        'calculated_discount_amount' => 'decimal:2',
        'original_product_value' => 'decimal:2',
        'discounted_product_value' => 'decimal:2',
        'taxable_value_before' => 'decimal:2',
        'taxable_value_after' => 'decimal:2',
        'tax_before' => 'decimal:2',
        'tax_after' => 'decimal:2',
        'applied_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function storeCreditRedemption()
    {
        return $this->belongsTo(CustomerCredit::class, 'store_credit_redemption_id');
    }

    public function reversal()
    {
        return $this->belongsTo(self::class, 'reversed_by_discount_id');
    }

    public function scopeApplied(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_APPLIED);
    }

    public function scopeForTarget(Builder $q, DiscountTargetType $type, int $id): Builder
    {
        return $q->where('target_type', $type->value)->where('target_id', $id);
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }
}
