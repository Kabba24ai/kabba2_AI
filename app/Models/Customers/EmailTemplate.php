<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class EmailTemplate extends Model
{
    protected $table = 'email_templates';

    protected $fillable = [
        'unique_id',
        'email_category_id',
        'name',
        'subject',
        'body',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EML-TPL');
        });
    }

    public function category()
    {
        return $this->belongsTo(EmailCategory::class, 'email_category_id');
    }
}
