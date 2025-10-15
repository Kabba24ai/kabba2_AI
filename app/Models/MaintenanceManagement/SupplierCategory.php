<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class SupplierCategory extends Model
{
    use HasFactory;

    protected $fillable = ['unique_id', 'name'];


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SUP-CAT');
        });
    }

    // Relationship: One Category → Many Suppliers
    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'supplier_category_id', 'id');
    }
}
