<?php

namespace App\Models\Stores;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'store_name',
        'phone',
        'email',
        'address',
        'state_id',
        'city',
        'zip_code',
        'is_primary', // Yes, No*
        'status', // Active* , Inactive, Archive
        'created_by',
        'updated_by',
    ];

    public function scopeOrderByAdmin($query)
    {
        return $query->orderBy('title', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function state()
{
    return $this->belongsTo(\App\Models\Locations\State::class);
}


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'STO');

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
}
