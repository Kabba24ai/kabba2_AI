<?php

namespace App\Http\Resources\Api\Admin\V1\Clients;

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
            'unique_id' => $this->unique_id ?? '',
            'name' => $this->name ?? '',
            'api_url' => $this->api_url ?? '',
            'admin_url' => $this->admin_url ?? '',
            'front_url' => $this->front_url ?? '',
        ];

        return $return;
    }
}
