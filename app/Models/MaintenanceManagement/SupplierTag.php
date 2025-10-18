<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class SupplierTag extends Model
{
    use HasFactory;

    protected $fillable = ['unique_id', 'name'];

    protected $appends = ['usedBy']; 


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SUP-TAG');
        });
    }

    // Computed attribute for how many suppliers use this tag
    public function getUsedByAttribute()
    {
        return Supplier::whereRaw(
            "FIND_IN_SET(?, tags)",
            [$this->id]
        )->count();
    }

}
