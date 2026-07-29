<?php

namespace App\Http\Resources\Api\Admin\V1\EquipmentRentalReady;

use App\Http\Resources\Api\Admin\V1\Equipment\ListResource as EquipmentListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Same response shape as App\Http\Resources\Api\Admin\V1\Equipment\ListResource
     * (the shared /equipment admin app endpoint) — built by delegating to it —
     * plus a handful of rental-ready-only fields (service_status, is_assigned,
     * rental_ready_checklist) that the rental-ready screen needs on top.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $base = (new EquipmentListResource($this->resource))->toArray($request);

        return array_merge($base, [
            'service_status' => $this->service_status ?? 'empty',
            'is_assigned' => (bool) ($this->is_assigned ?? false),

            // Same shape/order as the web screen's get-checklist-questions call:
            // {success, questions:[...]} for a fresh template, or
            // {success, existing_data:{questions:[...], counts, general_notes, existingTemplate}}
            // for one with saved answers. Null when the equipment has no checklist assigned.
            'rental_ready_checklist' => $this->rental_ready_checklist ?? null,
        ]);
    }
}
