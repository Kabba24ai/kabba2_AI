<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\MediaHelper;
use App\Helpers\ModelHelper;

// Models
use App\Models\Global\Media;

class EquipmentMedia extends Model
{
    protected $table = 'equipment_media';

    protected $fillable = [
        'unique_id',
        'equipment_id',
        'media_id',
        'created_by',
        'updated_by',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EQP-MED');
        });

        static::deleting(function ($model) {
            if ($model->media) {
                MediaHelper::removeFile($model->media);
            }
        });
    }
}
