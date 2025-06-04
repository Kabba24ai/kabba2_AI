<?php

namespace App\Models\ProductManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'type', // 'Rental', 'Retail'
        'description',
        'status', // 'Active', 'Inactive'
    ];

    public function items()
    {
        return $this->hasMany(ProductOptionItem::class)->orderBy('sort_order');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'POPT');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }

    public function isActive()
    {
        return $this->status === 'Active';
    }
}
