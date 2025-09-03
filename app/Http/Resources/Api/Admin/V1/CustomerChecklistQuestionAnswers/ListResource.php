<?php

namespace App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestionAnswers;

use App\Helpers\CustomHelper;
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
            'answer_delivery_text' => $this->answer_delivery_text ?? '',
            'answer_return_text' => $this->answer_return_text ?? '',
            'delivery_amt' => CustomHelper::formatCurrency($this->delivery_amt) ?? '',
            'return_amt' => CustomHelper::formatCurrency($this->return_amt) ?? '',
            'required' => $this->required ?? false,
            'sync_texts' => $this->sync_texts ?? [],
            'answer_sync_map' => $this->answer_sync_map ?? [],
            'question_id' => $this->question_id ?? 0,
            'index_number' => $this->index_number ?? 0,
        ];

        return $return;
    }
}
