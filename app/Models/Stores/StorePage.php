<?php

namespace App\Models\Stores;

use App\Models\Global\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'status', // Active*, Inactive
        'page_heading',
        'intro_text',
        'description',
        'image_media_id',
        'show_contact_strip',
        'seo_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image_media_id',
        'canonical_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_contact_strip' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
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

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function image()
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function ogImage()
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }
}
