<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;

class OrderProductChecklistQuestion extends Model
{
    protected $fillable = [
        'unique_id',
        'order_id',
        'order_product_id',
        'question_id',
        'question_category_id',
        'question_name',
        'delivery_question',
        'return_question',
        'index_number',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD-QUE');
        });
    }

    public function scopeIndexOrder($query)
    {
        return $query->orderBy('index_number', 'asc');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function question()
    {
        return $this->belongsTo(CustomerAdminQuestion::class, 'question_id');
    }

    public function questionCategory()
    {
        return $this->belongsTo(CustomerAdminCategory::class, 'question_category_id');
    }

    public function answers()
    {
        return $this->hasMany(OrderProductChecklistQuestionAnswers::class, 'order_product_checklist_question_id');
    }

    public function deliverySelectedAnswer()
    {
        return $this->hasOne(OrderProductChecklistQuestionAnswers::class, 'order_product_checklist_question_id')->where('is_delivery_answer', true);
    }

    public function returnSelectedAnswer()
    {
        return $this->hasOne(OrderProductChecklistQuestionAnswers::class, 'order_product_checklist_question_id')->where('is_return_answer', true);
    }


    public function latestAnswer()
    {
        return $this->hasOne(OrderProductChecklistQuestionAnswers::class, 'order_product_checklist_question_id')
            ->latestOfMany('index_number'); // always get last one
    }

    public function latestValidAnswer()
    {
        return $this->hasOne(OrderProductChecklistQuestionAnswers::class, 'order_product_checklist_question_id')
            ->latestOfMany('index_number'); // get last valid one
    }

    /**
     * Accessor: prefer latest valid (like row 106), fallback to latest (row 107).
     */
    public function getEffectiveLatestAnswerAttribute()
    {
        return $this->latestValidAnswer ?? $this->latestAnswer;
    }
}
