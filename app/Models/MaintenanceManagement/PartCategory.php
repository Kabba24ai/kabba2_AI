<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class PartCategory extends Model
{
    use HasFactory;

    protected $fillable = ['unique_id', 'name'];

    protected $appends = ['usedBy'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'PART-CAT');
        });
    }

    // Relationship: One Category → Many Parts
    public function parts()
    {
        return $this->hasMany(Part::class, 'part_category_id', 'id');
    }

    // Computed attribute for used count
    public function getUsedByAttribute()
    {
        return $this->parts()->count();
    }
}

