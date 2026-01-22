<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;

class OrderProductChecklistQuestionAnswers extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'order_product_checklist_question_id',
        'question_id',
        'answer_id',
        'delivery_answer',
        'return_answer',
        'delivery_amount',
        'return_amount',
        'user_delivery_amount',
        'user_return_amount',
        'is_delivery_answer',
        'is_return_answer',
        'is_sync',
        'index_number',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-ANS');
        });
    }

    public function orderProductChecklistQuestion()
    {
        return $this->belongsTo(OrderProductChecklistQuestion::class, 'order_product_checklist_question_id');
    }

    public function answer()
    {
        return $this->belongsTo(CustomerAdminQuestionAnswer::class, 'answer_id');
    }
}
