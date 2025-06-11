<?php

namespace App\Models\ProductManagement;

use App\Models\Global\Media;
use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\MediaHelper;

class ProductMediaChild extends Model
{
    protected $fillable = [
        'product_id',
        'media_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

   public function media()
    {
        return $this->belongsTo(Media::class, 'media_id', 'id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            // Handle any cleanup or related deletions if necessary
            // For example, delete associated media if needed
            if ($model->media) {
                MediaHelper::removeFile($model->media);
            }
        });
    }

}
