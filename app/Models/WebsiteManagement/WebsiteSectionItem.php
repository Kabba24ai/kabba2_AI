<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class WebsiteSectionItem extends Model
{
    protected $table = 'website_section_items';

    protected $fillable = [
        'unique_id',
        'website_page_section_id',
        'item_key',
        'title',
        'subtitle',
        'description',
        'image',
        'icon',
        'button_text',
        'button_url',
        'content',
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
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WSI');
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

    public function section()
    {
        return $this->belongsTo(WebsitePageSection::class, 'website_page_section_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        $media = \App\Models\Global\Media::find($this->image);
        return $media?->url;
    }
}
