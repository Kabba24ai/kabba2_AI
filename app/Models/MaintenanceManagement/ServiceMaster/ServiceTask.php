<?php

namespace App\Models\MaintenanceManagement\ServiceMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_tasks';

    protected $fillable = [
        'name',
        'description',
        'estimated_duration',
        'category_id',
        'auto_apply',
        'instructions',
    ];

    protected $casts = [
        'auto_apply' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function templateTasks()
    {
        return $this->hasMany(ServiceTemplateTask::class, 'task_id');
    }
}
