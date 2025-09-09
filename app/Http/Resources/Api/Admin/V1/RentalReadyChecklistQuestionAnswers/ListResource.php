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
            $label = $this['text'] ?? $this['label'] ?? '';
            $return = [
                'id' => $this['id'] ?? 0,
                'label' => $label  ?? '',
                'status' => $this['status'] ?? '',
            ];
        } else {

            $return = [
                'id' => $this->id ?? 0,
                'label' => $this->answer_name ?? '',
                'status' => $this->type ?? '',
            ];
        }

        return $return;
    }
}
