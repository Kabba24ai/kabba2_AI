<?php

namespace App\Http\Resources\Api\Admin\V1\RentalReadyChecklistCategories;

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
            'category_name' => $this->category_name ?? '',
            'description' => $this->description ?? '',
        ];

        return $return;
    }
}
