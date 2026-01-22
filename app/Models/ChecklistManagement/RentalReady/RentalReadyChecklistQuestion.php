<?php

namespace App\Models\ChecklistManagement\RentalReady;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalReadyChecklistQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = ['unique_id', 'question_name', 'category_id', 'required_question'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'QST'); // Prefix QST
            }
        });

        //  When question is deleted (soft or hard), also delete pivot links
        static::deleting(function ($model) {
            // Also soft-delete all linked template questions
            $model->templateQuestions()->delete();
        });
    }

    public function category() {
        return $this->belongsTo(RentalReadyChecklistCategory::class, 'category_id');
    }

    public function answers() {
        return $this->hasMany(RentalReadyChecklistQuestionAnswer::class, 'question_id')->orderBy('index_number', 'asc');
    }
    public function templateQuestions()
    {
        return $this->hasMany(RentalReadyChecklistTemplateQuestion::class, 'question_id');
    }
}
