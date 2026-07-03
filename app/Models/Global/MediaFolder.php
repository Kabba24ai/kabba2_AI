<?php

namespace App\Models\Global;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class MediaFolder extends Model
{
    protected $table = 'media_folders';

    protected $fillable = [
        'unique_id',
        'name',
        'slug',
        'parent_id',
        'created_by',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'MFD');
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'media_folder_id');
    }
}
