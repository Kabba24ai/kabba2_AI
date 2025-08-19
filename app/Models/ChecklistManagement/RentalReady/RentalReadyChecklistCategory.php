<?php

namespace App\Models\ChecklistManagement\RentalReady;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class RentalReadyChecklistCategory extends Model
{
    protected $fillable = ['unique_id', 'category_name', 'description'];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CAT'); // Prefix CAT
            }
        });
    }



    public function questions() {
        return $this->hasMany(RentalReadyChecklistQuestion::class, 'category_id');
    }



}
