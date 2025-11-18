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
        'country', // USA
        'state_id',
        'city',
        'zip_code',
        'latitude',
        'longitude',
        'details',
        'is_primary', // Yes, No*
        'status', // Active* , Inactive, Archive
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'full_address',
    ];

    public function scopeOrderByAdmin($query)
    {
        return $query->orderBy('store_name', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', 'Yes');
    }

    public function state()
    {
        return $this->belongsTo(\App\Models\Locations\State::class);
    }

    public function getFullAddressAttribute()
    {
        $parts = [
            $this->address,
            $this->city,
            optional($this->state)->name,
            $this->zip_code
        ];

        return implode(', ', array_filter($parts));
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

        self::saving(function ($model) {
            // Set updated_by
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function hours()
    {
        return $this->hasMany(HoursOfOperation::class);
    }

    public function hoursOfOperation()
    {
        return $this->hasMany(HoursOfOperation::class);
    }
}
