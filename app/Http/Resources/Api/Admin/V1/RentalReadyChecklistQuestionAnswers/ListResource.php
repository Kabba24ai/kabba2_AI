<?php

namespace App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestionAnswers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',
            'answer_name' => $this->answer_name ?? '',
            'question_id' => $this->question_id ?? 0,
            'type' => $this->type ?? '',
            'index_number' => $this->index_number ?? 0,
        ];

        return $return;
    }
}
