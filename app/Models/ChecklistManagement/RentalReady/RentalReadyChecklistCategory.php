<?php

namespace App\Models\ChecklistManagement\RentalReady;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalReadyChecklistCategory extends Model
{

    use SoftDeletes;

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
