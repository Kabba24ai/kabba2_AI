<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Enums
use App\Enums\Orders\OrderMediaType;
use App\Helpers\MediaHelper;
// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;

class OrderMedia extends Model
{
    protected $fillable = [
        'unique_id',
        'type', // e.g., license, delivery, pickup
        'order_id',
        'order_product_id', // Nullable if not applicable
        'media_id',
        'created_by',
        'updated_by'
    ];

    protected $table = 'order_media';

    protected $casts = [
        'type' => OrderMediaType::class, // Assuming you have an enum for media types
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-MED');
        });

        static::deleting(function ($model) {
            // Handle any cleanup or related deletions if necessary
            // For example, delete associated media if needed
            if ($model->media) {
                MediaHelper::removeFile($model->media);
                $model->media->delete();
            }
        });
    }
}
