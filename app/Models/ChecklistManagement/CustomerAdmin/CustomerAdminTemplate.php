<?php

namespace App\Models\ChecklistManagement\CustomerAdmin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ModelHelper;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductManagement\ProductCategory;


class CustomerAdminTemplate extends Model
{

    use HasFactory;

    protected $fillable = [
        'template_name',
        'description',
        'equipment_category_id',
        'active_template',
        'unique_id',
    ];

    public function questions()
    {
        return $this->hasMany(CustomerAdminTemplateQuestion::class, 'template_id');
    }

    public function templateQuestions()
    {
        return $this->hasMany(CustomerAdminTemplateQuestion::class, 'template_id')->orderBy('index_number', 'asc');
    }

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                // Generate a unique ID with prefix 'TQST'
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CATQS');
            }
        });
    }

    public function equipmentCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'equipment_category_id');
    }


}
