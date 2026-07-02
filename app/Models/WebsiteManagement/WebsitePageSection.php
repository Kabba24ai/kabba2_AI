<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class WebsitePageSection extends Model
{
    protected $table = 'website_page_sections';

    protected $fillable = [
        'unique_id',
        'website_page_id',
        'section_key',
        'section_name',
        'title',
        'subtitle',
        'content',
        'image',
        'button_text',
        'button_url',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WPS');
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

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'website_page_id');
    }

    public function items()
    {
        return $this->hasMany(WebsiteSectionItem::class)->orderBy('display_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        $media = \App\Models\Global\Media::find($this->image);
        return $media?->url;
    }
}
