<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntervalPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'intervals'
    ];

    protected $casts = [
        'intervals' => 'array'
    ];

    public function templates()
    {
        return $this->hasMany(ServiceTemplate::class, 'preset_id');
    }
}