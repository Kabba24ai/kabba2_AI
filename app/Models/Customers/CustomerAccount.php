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
        'responsible_person',
        'notes',
        'date',
        'payment_number_id',
        'reason',
        'sales_tax',
      
        'type',
       
    ];

    protected $casts = [
        'date' => 'datetime',
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
    return $this->belongsTo(User::class, 'responsible_person');
}


    

    /**
     * Relationship to the Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
