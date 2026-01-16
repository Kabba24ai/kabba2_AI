<?php

namespace App\Http\Resources\Api\Admin\V1\UserNotification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform resource into array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            
            'title'     => $this->title ?? '',
            'body'      => $this->body ?? '',

            'type'      => $this->type ?? null,
            'params'    => $this->params ?? [],
        ];
    }
}
