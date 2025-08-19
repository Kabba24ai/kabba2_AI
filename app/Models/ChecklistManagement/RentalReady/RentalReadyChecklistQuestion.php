<?php

namespace App\Models\ChecklistManagement\RentalReady;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class RentalReadyChecklistQuestion extends Model
{
    protected $fillable = ['unique_id', 'question_name', 'category_id', 'required_question'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'QST'); // Prefix QST
            }
        });
    }


    public function category() {
        return $this->belongsTo(RentalReadyChecklistCategory::class, 'category_id');
    }

    public function answers() {
        return $this->hasMany(RentalReadyChecklistQuestionAnswer::class, 'question_id');
    }
}
