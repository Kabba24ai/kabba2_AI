<?php

namespace App\Models\Opportunity;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class OpportunityQuestion extends Model
{
    protected $fillable = [
        'unique_id',
        'type',
        'question_key',
        'question_text',
        'required',
        'answer_type',
        'sub_text',
        'display_order',
        'answer_grid',
        'status'
    ];

       public static function boot()
        {
            parent::boot();

            self::creating(function ($model) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'EOQ');
            });
        }

    public function options()
    {
        return $this->hasMany(OpportunityQuestionOption::class);
    }

    // public function answers()
    // {
    //     return $this->hasMany(OpportunityAnswer::class);
    // }
}

