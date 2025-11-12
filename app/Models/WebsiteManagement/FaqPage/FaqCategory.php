<?php

namespace App\Models\WebsiteManagement\FaqPage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class FaqCategory extends Model
{
    use HasFactory;

    protected $table = 'faq_categories';

    protected $fillable = [
        'unique_id',
        'category_name',
        'description',
        'default_expand',
        'category_icon_media_id',
        'category_index_number',
    ];

    protected $casts = [
        'default_expand' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'FAQ-CAT');

            // Auto-assign next index number if not manually provided
            if (is_null($model->category_index_number)) {
                $model->category_index_number = (self::max('category_index_number') ?? 0) + 1;
            }
        });
    }

    public function iconMedia()
    {
        return $this->belongsTo(\App\Models\Global\Media::class, 'category_icon_media_id');
    }
}
