<?php

namespace App\Http\Resources\Api\TimeTracker\V1\Users;

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
            'unique_id' => $this->unique_id ?? 0,
            'full_name' => $this->full_name ?? '',
            'email' => $this->email ?? '',
            'status' => $this->status ?? '',
        ];

        if(isset($this->token))
        {
            $return['token'] = $this->token;
        }

        return $return;
    }
}
