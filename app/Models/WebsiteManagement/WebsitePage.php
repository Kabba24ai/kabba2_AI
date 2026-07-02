<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    protected $table = 'website_pages';

    protected $fillable = [
        'unique_id',
        'page_key',
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'status',
        'created_by',
        'updated_by',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WPG');
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        self::saving(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function sections()
    {
        return $this->hasMany(WebsitePageSection::class)->orderBy('display_order');
    }

    public function sectionByKey(string $key): ?WebsitePageSection
    {
        return $this->sections()->where('section_key', $key)->first();
    }
}
