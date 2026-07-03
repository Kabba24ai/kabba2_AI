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
        'publish_status',
        'published_at',
        'published_revision_id',
        'auto_save_data',
        'auto_saved_at',
        'header_message',
        'header_highlight',
        'header_badge',
        'header_callout',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'auto_saved_at' => 'datetime',
        'auto_save_data' => 'array',
    ];

    const PUBLISH_STATUS_DRAFT     = 'draft';
    const PUBLISH_STATUS_PUBLISHED = 'published';
    const PUBLISH_STATUS_ARCHIVED  = 'archived';

    public function isPublished(): bool { return $this->publish_status === self::PUBLISH_STATUS_PUBLISHED; }
    public function isDraft(): bool     { return $this->publish_status === self::PUBLISH_STATUS_DRAFT; }
    public function isArchived(): bool  { return $this->publish_status === self::PUBLISH_STATUS_ARCHIVED; }

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

    public function revisions()
    {
        return $this->hasMany(WebsitePageRevision::class)->orderByDesc('revision_number');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(WebsitePageRevision::class, 'published_revision_id');
    }

    public function revisionLogs()
    {
        return $this->hasMany(WebsiteRevisionLog::class)->orderByDesc('created_at');
    }
}
