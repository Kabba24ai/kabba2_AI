<?php

namespace App\Models\Orders;

use App\Helpers\ModelHelper;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'note',
        'note_type',
        'user_id',
        'created_by_type',
        'created_by_id',
        'updated_by_type',
        'updated_by_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->morphTo('created_by');
    }

    public function updatedBy()
    {
        return $this->morphTo('updated_by');
    }

    public function getCreatedByTypeNameAttribute()
    {
        return $this->createdBy ? class_basename($this->createdBy) : null;
    }

    public function getUpdatedByTypeNameAttribute()
    {
        return $this->updatedBy ? class_basename($this->updatedBy) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-NOTE');
        });
    }

    public function isCreatedByCustomer()
    {
        return $this->created_by_type_name === 'Customer';
    }
    public function scopeDashboard($query)
{
    return $query->where('note_type', 'dashboard');
}
    
}
