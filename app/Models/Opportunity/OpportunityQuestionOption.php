<?php

namespace App\Models\Opportunity;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class OpportunityQuestionOption extends Model
{
    protected $fillable = [
        'opportunity_question_id',
        'value',
        'label',
        'display_order',
        'status'
    ];

       public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'EOQO');
        });
    }

    public function question()
    {
        return $this->belongsTo(OpportunityQuestion::class);
    }
}

