<?php

namespace App\Models\Service;

use App\Helpers\ModelHelper;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer Damage Staging — one field-reported damage observation awaiting
 * an office disposition. The record OWNS only the review workflow state and
 * the snapshotted observation; every canonical entity (order, customer,
 * equipment, charge, ticket) is a reference, never a copy.
 *
 * Lifecycle: new → in_review → disposed. Disposition (no_action |
 * charge_customer | service_ticket) records the primary outcome; the
 * ticket/charge links are independent, so one record may legitimately end
 * up with BOTH a service ticket and a billing charge.
 */
class CustomerDamageStaging extends Model
{
    public const STATUS_NEW       = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_DISPOSED  = 'disposed';

    public const DISPOSITION_NO_ACTION      = 'no_action';
    public const DISPOSITION_CHARGE         = 'charge_customer';
    public const DISPOSITION_SERVICE_TICKET = 'service_ticket';

    protected $table = 'customer_damage_stagings';

    protected $fillable = [
        'unique_id',
        'source_type',
        'source_key',
        'order_id',
        'customer_id',
        'order_product_id',
        'equipment_id',
        'store_id',
        'observation',
        'reported_by',
        'reported_at',
        'status',
        'disposition',
        'disposition_note',
        'disposed_by',
        'disposed_at',
        'service_ticket_id',
        'billing_charge_id',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'disposed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CDS');
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function disposedBy()
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function serviceTicket()
    {
        return $this->belongsTo(ServiceTicket::class);
    }

    public function billingCharge()
    {
        return $this->belongsTo(BillingCharge::class);
    }

    // ── Scopes / state ──────────────────────────────────────────────────

    /** The default working queue: everything not yet disposed. */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_DISPOSED);
    }

    public function isDisposed(): bool
    {
        return $this->status === self::STATUS_DISPOSED;
    }
}
