<?php

namespace App\Http\Resources\Api\Admin\V1\CustomerNotes;

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
            'unique_id' => $this->unique_id ?? 0,
            'description' => $this->description ?? '',
            'created_by' => $this->user ? $this->user->full_name ?? 'Unknown' : '',
            'created_date' => $this->created_date ? CustomHelper::formatDate($this->created_date) : '',
            'created_time' => $this->created_time ? CustomHelper::formatTime($this->created_time) : '',
        ];

        return $return;
    }
}
