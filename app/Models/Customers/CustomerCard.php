<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class CustomerCard extends Model
{
    protected $fillable = [
        'unique_id',
        'customer_id',
        'payment_profile_id',
        'first_name',
        'last_name',
        'card_number',
        'card_type',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getCardTypeAttribute($value)
    {
        return ucfirst(strtolower($value));
    }

    public function getCardExpiryAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('m/y');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CUST-CARD');
        });
    }
}
