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
            $label = $this['label'] ?? '';
            $status = $this['status'] ?? '';
        } else {
            $label = $this->answer_name ?? '';
            $status = $this->type ?? '';
        }


        $return = [
            'id' => $this->id ?? 0,
            'label' => $label ?? '',
            'status' => $status ?? '',
        ];

        return $return;
    }
}
