<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class EmailCategory extends Model
{
    protected $table = 'email_categories';

    protected $fillable = [
        'unique_id',
        'name',
        'description',
    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EMAIL-CAT');
        });
    }
}
