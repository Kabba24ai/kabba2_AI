<?php

namespace App\Models\MaintenanceManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipments';

    protected $fillable = [
        'unique_id',
        'equipment_name',
        'category',
        'equipment_id',
        'equipment_hours',
        'brand',
        'model',
        'model_year',
        'date_acquired',
        'cost',
        'ownership_type',
        'finance_company',
        'term',
        'rate',
        'monthly_payment',
        'vin',
        'serial_number',
        'plate',
        'imei',
        'rental_ready_checklist',
        'equipment_service_list',
        'equipment_notes',
        'status',
        'tech_manager',
        'location',
        'service_interval',
        'current_hours',
        'last_service',
    ];

    protected $casts = [
        'date_acquired' => 'date',
        'last_service' => 'date',
        'cost' => 'decimal:2',
        'rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'model_year' => 'integer',
        'term' => 'integer',
        'service_interval' => 'integer',
        'current_hours' => 'integer',
    ];

    public function getServiceStatusAttribute()
    {
        if (!$this->service_interval || !$this->current_hours) {
            return ['status' => 'n/a', 'color' => 'text-gray-400'];
        }

        $percentComplete = ($this->current_hours % $this->service_interval) / $this->service_interval;

        if ($percentComplete >= 1) {
            return ['status' => 'overdue', 'color' => 'text-red-500'];
        } elseif ($percentComplete >= 0.8) {
            return ['status' => 'due-soon', 'color' => 'text-yellow-500'];
        } else {
            return ['status' => 'good', 'color' => 'text-green-500'];
        }
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'available' => 'bg-green-100 text-green-800 border-green-200',
            'rented' => 'bg-blue-100 text-blue-800 border-blue-200',
            'maintenance' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'damaged' => 'bg-red-100 text-red-800 border-red-200'
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }

    public function getTruncatedRentalReadyAttribute()
    {
        if (!$this->rental_ready_checklist) {
            return '-';
        }
        
        return strlen($this->rental_ready_checklist) > 16 
            ? substr($this->rental_ready_checklist, 0, 16) . '...'
            : $this->rental_ready_checklist;
    }

    public function getTruncatedServiceListAttribute()
    {
        if (!$this->equipment_service_list) {
            return '-';
        }
        
        return strlen($this->equipment_service_list) > 16 
            ? substr($this->equipment_service_list, 0, 16) . '...'
            : $this->equipment_service_list;
    }
}
