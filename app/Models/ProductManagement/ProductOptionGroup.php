<?php

namespace App\Models\ProductManagement;

use App\Helpers\ModelHelper;
use App\Models\Global\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'product_option_id',
        'name',
        'message',
        'media_id',
        'status', // 'Active', 'Inactive'
        'sort_order',
    ];

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }

    public function items()
    {
        return $this->belongsToMany(ProductOptionItem::class, 'product_option_group_items')->withTimestamps();
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'POPT-GRP');
        });
    }

    public function getImageUrlAttribute()
    {
        return $this->media?->getUrl() ?? asset('storage/front/images/option-image.png');
    }
}
