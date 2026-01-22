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
        if (is_array($this->resource)) {
            $return = [
                'id' => $this['id'] ?? 0,
                "unique_id" => $this['unique_id'] ?? "",
                "answer_name" => $this['answer_name'] ?? "",
                "type" => $this['type'] ?? "",
                "is_selected" => $this['is_selected'] ?? false,
            ];
        } else {

            $return = [
                'id' => $this->id ?? 0,
                "unique_id" => $this->unique_id ?? "",
                "answer_name" => $this->answer_name ?? "",
                "type" => $this->type ?? "",
                "is_selected" => false,
            ];
        }

        return $return;
    }
}
