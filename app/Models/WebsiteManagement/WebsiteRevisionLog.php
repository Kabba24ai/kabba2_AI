<?php

namespace App\Models\WebsiteManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class WebsiteRevisionLog extends Model
{
    protected $table = 'website_revision_logs';

    protected $fillable = [
        'unique_id',
        'website_page_id',
        'website_page_revision_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const ACTIONS = [
        'draft_saved'      => 'Draft Saved',
        'auto_saved'       => 'Auto-Saved',
        'published'        => 'Published',
        'unpublished'      => 'Unpublished',
        'archived'         => 'Archived',
        'restored'         => 'Restored',
        'revision_deleted' => 'Revision Deleted',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'WRL');
        });
    }

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'website_page_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'user_id');
    }

    public function revision()
    {
        return $this->belongsTo(WebsitePageRevision::class, 'website_page_revision_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
