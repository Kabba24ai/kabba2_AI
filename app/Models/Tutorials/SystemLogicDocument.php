<?php

namespace App\Models\Tutorials;

use App\Enums\Tutorials\SystemLogicStatus;
use App\Enums\Tutorials\SystemLogicVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemLogicDocument extends Model
{
    use SoftDeletes;

    protected $table = 'system_logic_documents';

    protected $fillable = [
        'module_key',
        'section_key',
        'title',
        'summary',
        'logic_body',
        'status',
        'visibility',
        'sort_order',
        'created_by',
        'updated_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'status'      => SystemLogicStatus::class,
        'visibility'  => SystemLogicVisibility::class,
        'reviewed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'updated_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'reviewed_by');
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module_key', $module);
    }

    public function scopeForSection($query, string $section)
    {
        return $query->where('section_key', $section);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SystemLogicStatus::Active->value);
    }
}
