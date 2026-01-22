<?php

namespace App\Http\Resources\Api\Admin\V1\OrderMedias;

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
            'type' => $this->type ?? '',
            'media_url' => $this->media->url ?? '',
            'media_name' => $this->media->file_name ?? '',
            'media_type' => $this->media->file_type ?? '',
        ];

        return $return;
    }
}
