<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;


use App\Models\Orders\Order;
use App\Helpers\ModelHelper;
class CustomerAccount extends Model
{
    protected $table = 'customer_accounts';

    protected $fillable = [
        'unique_id',
        'customer_id',
        'order_id',
        'balance',
        'amount',
        'payment_type',
        'responsible_person_id',
        'responsible_person_name',
        'notes',
        'date',
        'payment_number_id',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'reason',
        'sales_tax',
        'sales_tax_type',
        'type',
        'invoice_id',
        'invoice_item_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'payment_type' => \App\Enums\Customers\PaymentMethod::class,

    ];

     /**
     * Boot model: auto-generate unique_id before creating.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ACC');
            }
        });
    }

    /**
     * Relationship to the Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // in CustomerAccount
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Relationship to the Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function card()
    {
        return $this->belongsTo(CustomerCard::class, 'payment_profile_id', 'payment_profile_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }


}
