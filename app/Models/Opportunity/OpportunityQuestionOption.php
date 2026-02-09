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

    public function question()
    {
        return $this->belongsTo(OpportunityQuestion::class);
    }
}

