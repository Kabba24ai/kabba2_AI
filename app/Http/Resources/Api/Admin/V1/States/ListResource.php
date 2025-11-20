<?php

namespace App\Http\Resources\Api\Admin\V1\States;


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
        return [
            'id' => $this->id ?? 0,
            'name' => $this->name ?? '',
            'slug' => $this->slug ?? '',
            'abbreviation' => $this->abbreviation ?? '',
        ];
    }
}
