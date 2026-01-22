<?php

namespace App\Http\Resources\Api\Admin\V1\OrderNotes;

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
            'note' => $this->note ?? '',
            'is_created_by_customer' => $this->isCreatedByCustomer() ?? false,
            'created_by_id' => $this->created_by_id ?? 0,
            'created_by' => $this->createdBy ? $this->createdBy->full_name ?? 'Unknown' : '',
            'created_by_type_name' => $this->createdBy ? $this->created_by_type_name ?? 'Unknown' : '',
            'created_at' => $this->createdBy ? CustomHelper::formatDateTime($this->created_at) : '',
            'updated_by_id' => $this->updated_by_id ?? 0,
            'updated_by' => $this->updatedBy ? $this->updatedBy->full_name ?? 'Unknown' : '',
            'updated_by_type_name' => $this->updatedBy ? $this->updated_by_type_name ?? 'Unknown' : '',
            'updated_at' => $this->updatedBy ? CustomHelper::formatDateTime($this->updated_at) : '',

        ];

        return $return;
    }
}
