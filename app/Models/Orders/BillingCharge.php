<?php

namespace App\Models\Orders;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceModule;
use App\Helpers\ModelHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingCharge extends Model
{
    use SoftDeletes;

    protected $table = 'billing_charges';

    protected $fillable = [
        'unique_id',
        'billing_charge_type',
        'status',
        'paid_at',
        'parent_order_id',
        'child_order_id',
        'customer_id',
        'store_id',
        'order_product_id',
        'amount',
        'tax_amount',
        'tax_type',
        'responsible_person_id',
        'responsible_person_name',
        'notes',
        'customer_account_id',
        'created_by_id',
        'source_module',
        'source_event',
        'source_reference_type',
        'source_reference_id',
        'metadata',
        'idempotency_key',
    ];

    protected $casts = [
        'amount'              => 'float',
        'tax_amount'          => 'float',
        'paid_at'             => 'datetime',
        'metadata'            => 'array',
        'billing_charge_type' => BillingChargeType::class,
        'status'              => BillingChargeStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'BLC');
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function childOrder()
    {
        return $this->belongsTo(Order::class, 'child_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function legacyCustomerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // ── Status helpers ─────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === BillingChargeStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === BillingChargeStatus::Paid;
    }

    public function isResolved(): bool
    {
        return $this->status === BillingChargeStatus::Resolved;
    }

    // ── Source helpers ─────────────────────────────────────────────────────

    public function isMobileOriginated(): bool
    {
        if ($this->source_module === null) {
            return false;
        }

        $module = BillingSourceModule::tryFrom($this->source_module);

        return $module?->isMobileOriginated() ?? false;
    }

    public function hasIdempotencyKey(): bool
    {
        return $this->idempotency_key !== null;
    }
}
