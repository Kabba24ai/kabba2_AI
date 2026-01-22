<?php

namespace App\Models\MaintenanceManagement\ServiceMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTemplateTask extends Model
{
    use HasFactory;

    protected $table = 'service_template_tasks';

    protected $fillable = [
        'template_id',
        'task_id',
        'sort_order',
        'intervals',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'intervals' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(ServiceTemplate::class, 'template_id');
    }

    public function task()
    {
        return $this->belongsTo(ServiceTask::class, 'task_id');
    }
}
