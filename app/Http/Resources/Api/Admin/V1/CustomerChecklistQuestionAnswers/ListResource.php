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
       if ($this->answer_delivery_text) {
            $deliveryText = $this->answer_delivery_text ?? '';
        }else{
            $deliveryText = $this->delivery_answer ?? '';
        }

        if ($this->answer_return_text) {
            $returnText = $this->answer_return_text ?? '';
        }else{
            $returnText = $this->return_answer ?? '';
        }

        if ($this->delivery_amt) {
            $deliveryAmount = $this->delivery_amt ?? 0.0;
        } else {
            $deliveryAmount = $this->delivery_amount ?? 0.0;
        }

        if ($this->return_amt) {
            $returnAmount = $this->return_amt ?? 0.0;
        } else {
            $returnAmount = $this->return_amount ?? 0.0;
        }

        if($this->sync_texts){
            $isSync = $this->sync_texts;
        }else{
            $isSync = $this->is_sync;
        }


        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',
            'answer_delivery_text' => $deliveryText ?? '',
            'answer_return_text' => $returnText ?? '',
            'delivery_amt' => (float) $deliveryAmount,
            'return_amt' => (float) $returnAmount,

            'sync_texts' => $isSync,
            'question_id' => $this->question_id ?? 0,
            'index_number' => $this->index_number ?? 0,

            'user_delivery_amount' => (float) ($this->user_delivery_amount ?? 0.0),
            'user_return_amount' => (float) ($this->user_return_amount ?? 0.0),

            'is_delivery_answer' =>  (bool) $this->is_delivery_answer ?? false,
            'is_return_answer' => (bool) $this->is_return_answer ?? false,
        ];

        return $return;
    }
}
