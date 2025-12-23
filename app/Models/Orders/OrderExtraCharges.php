<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class OrderExtraCharges extends Model
{
    use HasFactory;

    protected $table = 'order_extra_charges';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'unique_id',
        'order_id',
        'order_product_id',
        'customer_id',
        'amount',
        'type',
        'payment_type',
        'payment_number_id',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'responsible_person_id',
        'responsible_person_name',
        'notes',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Auto-generate UUID
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-EXT-PAY');
            }
        });
    }

    /* ==========================
     | Relationships
     ========================== */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'responsible_person_id');
    }

    /* ==========================
     | Helpers / Scopes
     ========================== */

    public function scopeFuel($query)
    {
        return $query->where('type', 'fuel');
    }

    public function scopeDamage($query)
    {
        return $query->where('type', 'damage');
    }

}
