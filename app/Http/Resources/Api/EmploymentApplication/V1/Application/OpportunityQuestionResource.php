<?php

namespace App\Http\Resources\Api\EmploymentApplication\V1\Application;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'unique_id'     => $this->unique_id,
            'type'          => $this->type,
            'question_key'  => $this->question_key,
            'question_text' => $this->question_text,
            'sub_text'      => $this->sub_text,
            'required'      => (bool) $this->required,
            'answer_type'   => $this->answer_type,
            'display_order' => $this->display_order,
            'answer_grid' => $this->answer_grid,
            'status' => $this->status ,
            'options'       => $this->options->map(function ($option) {
                return [
                    'value' => $option->value,
                    'label' => $option->label,
                    'display_order' => $option->display_order,
                    'status' => $option->status,
                ];
            })->values(),
        ];
    }
}
