<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsitePageRevision extends Model
{
    use SoftDeletes;

    protected $table = 'website_page_revisions';

    protected $fillable = [
        'unique_id',
        'website_page_id',
        'revision_number',
        'created_by',
        'change_summary',
        'snapshot',
        'is_published_snapshot',
        'restored_from_revision_id',
    ];

    protected $casts = [
        'snapshot'              => 'array',
        'is_published_snapshot' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WPR');
        });
    }

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'website_page_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'created_by');
    }

    public function restoredFrom()
    {
        return $this->belongsTo(self::class, 'restored_from_revision_id');
    }

    public function getPageTitleAttribute(): string
    {
        return $this->snapshot['page']['title'] ?? '—';
    }
}
