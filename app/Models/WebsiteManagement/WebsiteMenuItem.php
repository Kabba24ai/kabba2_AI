<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use App\Models\Global\Media;
use Illuminate\Database\Eloquent\Model;

class WebsiteMenuItem extends Model
{
    protected $table = 'website_menu_items';

    protected $fillable = [
        'unique_id',
        'website_menu_id',
        'parent_id',
        'title',
        'type',
        'page_id',
        'url',
        'icon',
        'icon_media_id',
        'css_class',
        'target',
        'rel',
        'visibility',
        'display_order',
        'status',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    protected $appends = ['resolved_url'];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WMI');
        });
    }

    public function menu()
    {
        return $this->belongsTo(WebsiteMenu::class, 'website_menu_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }

    public function iconMedia()
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function getResolvedUrlAttribute(): ?string
    {
        if ($this->type === 'internal_page') {
            return $this->page ? '/' . ltrim($this->page->slug, '/') : null;
        }
        if ($this->type === 'email') {
            $addr = ltrim($this->url ?? '', 'mailto:');
            return $addr ? 'mailto:' . $addr : null;
        }
        if ($this->type === 'phone') {
            return $this->url ? 'tel:' . preg_replace('/[^+0-9]/', '', $this->url) : null;
        }
        return $this->url ?: null;
    }

    /** Serialized array for the builder UI and AJAX responses. */
    public function toBuilderArray(): array
    {
        return [
            'unique_id'       => $this->unique_id,
            'title'           => $this->title,
            'type'            => $this->type,
            'page_id'         => $this->page_id,
            'page_title'      => $this->page?->title,
            'page_slug'       => $this->page?->slug,
            'url'             => $this->url,
            'resolved_url'    => $this->resolved_url,
            'icon'            => $this->icon,
            'icon_media_id'   => $this->icon_media_id,
            'css_class'       => $this->css_class,
            'target'          => $this->target,
            'rel'             => $this->rel,
            'visibility'      => $this->visibility,
            'status'          => $this->status,
            'content'         => $this->content,
        ];
    }
}
