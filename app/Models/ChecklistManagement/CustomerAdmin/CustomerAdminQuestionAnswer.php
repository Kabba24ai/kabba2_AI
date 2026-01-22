<?php

namespace App\Models\ChecklistManagement\CustomerAdmin;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

class CustomerAdminQuestionAnswer extends Model
{
    protected $fillable = ['unique_id', 'answer_delivery_text', 'answer_return_text', 'delivery_amt', 'return_amt', 'required', 'sync_texts', 'answer_sync_map', 'question_id', 'index_number' , 'is_damaged'];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'CAANS'); // Prefix ANS
            }
        });
    }


    public function question() {
        return $this->belongsTo(CustomerAdminQuestion::class, 'question_id');
    }
}
