<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3.0 — CustomerCreditService foundation.
 *
 * One row per credit event (a `grant` or a `redemption`) for a customer's
 * Financial Store Credit balance. Deliberately separate from
 * `CustomerAccount` — see PHASE_3_0_PRE_IMPLEMENTATION_CHECKLIST.md for why.
 *
 * This model has no business logic of its own beyond relationships and
 * casts. Every decision about whether a grant or redemption is valid lives
 * in {@see \App\Services\CustomerCreditService}, not here — the same
 * "model is data, service is policy" separation `CustomerAccount` and
 * `LedgerBalanceService` already maintain.
 */
class CustomerCredit extends Model
{
    use SoftDeletes;

    protected $table = 'customer_credits';

    protected $fillable = [
        'unique_id',
        'customer_id',
        'order_id',
        'order_payment_id',
        'type',
        'amount',
        'reason',
        'effective_date',
        'idempotency_key',
        'responsible_person_id',
        'responsible_person_name',
        'notes',
        'internal_comments',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
    ];

    /**
     * Auto-generate unique_id before creating, identical pattern to
     * CustomerAccount::boot().
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CC');
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Phase 3.2 — Order Entry Integration. Nullable: most grants/redemptions
     * are not tied to a specific order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
