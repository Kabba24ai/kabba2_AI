<?php

namespace App\Models\ChecklistManagement\CustomerAdmin;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class CustomerAdminQuestion extends Model
{
    protected $fillable = ['unique_id', 'question_name', 'category_id', 'question_delivery_text', 'question_return_text', 'required_question'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CAQST'); // Prefix QST
            }
        });
    }


    public function category() {
        return $this->belongsTo(CustomerAdminCategory::class, 'category_id');
    }

    public function answers() {
        return $this->hasMany(CustomerAdminQuestionAnswer::class, 'question_id')->orderBy('index_number', 'asc');
    }
}
