<?php

namespace App\Models\Clients;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'unique_id',
        'name',
        'code',
        'api_url',
        'admin_url',
        'front_url',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CLT');
        });
    }

}
