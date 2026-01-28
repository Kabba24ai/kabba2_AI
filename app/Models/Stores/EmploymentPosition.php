<?php

namespace App\Models\Stores;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class EmploymentPosition extends Model
{
     protected $table = 'employment_positions';

    protected $fillable = [
        'unique_id',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
       parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EOP');
        });
    }
}
