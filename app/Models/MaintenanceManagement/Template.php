<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'name',
        'category',
        'description',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = Str::uuid();
            }
        });
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'template_parts')
                    ->withPivot('sort_order')
                    ->orderBy('pivot_sort_order');
    }

    public function getPartsCountAttribute()
    {
        return $this->parts()->count();
    }
}
