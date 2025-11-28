<?php

namespace App\Models\MaintenanceManagement;

use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'task_id',
        'intervals'
    ];

    protected $casts = [
        'intervals' => 'array'
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