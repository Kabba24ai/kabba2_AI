<?php

namespace App\Models\MaintenanceManagement\ServiceMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_categories';

    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    public function tasks()
    {
        return $this->hasMany(ServiceTask::class, 'category_id');
    }

    public function templates()
    {
        return $this->hasMany(ServiceTemplate::class, 'category_id');
    }
}
