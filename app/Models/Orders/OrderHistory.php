<?php
namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;

class OrderHistory extends Model
{
    protected $table = 'order_histories';

    protected $fillable = [
        'unique_id',
        'order_id',
        'customer_id',
        'user_id',
        'action_date',
        'action_by',
        'action',
        'description',
        'extras',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-HIST');
        });
    }
}
