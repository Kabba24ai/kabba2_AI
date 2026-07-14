<?php
namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Helpers
use App\Helpers\ModelHelper;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

// Models
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;

class OrderHistory extends Model
{
    use SoftDeletes;

    protected $table = 'order_histories';

    protected $fillable = [
        'unique_id',
        'order_id',
        'order_payment_id',
        'customer_id',
        'user_id',
        'action_date',
        'action_by', // Customer, User, System
        'action', // create_order, payment_initiated, order_paid, order_refunded, etc.
        'description',
        'extras',
    ];

    protected $casts = [
        'action_by' => OrderHistoryActionBy::class,
        'action' => OrderHistoryAction::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-HIST');
        });
    }
}
