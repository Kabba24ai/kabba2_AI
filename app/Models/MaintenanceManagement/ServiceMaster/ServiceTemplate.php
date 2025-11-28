<?php

namespace App\Models\MaintenanceManagement\ServiceMaster;

use App\Models\MaintenanceManagement\IntervalPreset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_templates';

    protected $fillable = [
        'name',
        'description',
        'preset_id',
    ];

    public function preset()
    {
        return $this->belongsTo(IntervalPreset::class, 'preset_id');
    }

    public function templateTasks()
    {
        return $this->hasMany(ServiceTemplateTask::class, 'template_id');
    }
}
