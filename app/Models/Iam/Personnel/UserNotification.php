<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;
// Helpers
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Orders\Order;

use Illuminate\Support\Str;

class UserNotification extends Model
{

 use HasFactory;

    protected $fillable = [
        'unique_id',
        'user_id',
        'order_id',
        'status',
        'type',
        'body',
        'params',
        'title',

    ];

       protected static function booted()
    {
        static::creating(function ($notification) {
             $notification->unique_id = ModelHelper::generateUniqueID($notification, 'UNH');
        });
    }


    /* Relationships */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
