<?php

namespace App\Http\Resources\Api\Admin\V1\EquipmentRentalReadyChecklist;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistCategories\ListResource as RentalReadyChecklistCategoriesListResource;
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestionAnswers\ListResource as RentalReadyChecklistQuestionAnswersListResource;


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
            'equipment_id' => $this->equipment_id ?? 0,
            'employee_id' => $this->employee_id ?? 0,
            'employee_name' => $this->employee_name ?? '',
            'order_id' => $this->order_id ?? 0,
            'order_product_id' => $this->order_product_id ?? 0,
            'inspection_date' => CustomHelper::formatDate($this->inspection_date) ?? '',
            'inspection_time' => CustomHelper::formatTime($this->inspection_time) ?? '',
            'equipment_hours' => $this->equipment_hours ?? 0,
            'general_notes' => $this->general_notes ?? '',
            'status' => $this->status ?? '',
            'is_complete' => $this->is_complete ?? false,
            'total_questions' => $this->total_questions ?? 0,
            'required_questions' => $this->required_questions ?? 0,
            'optional_questions' => $this->optional_questions ?? 0,
            'required_items_completed' => $this->required_items_completed ?? 0,
            'items_requiring_maintenance' => $this->items_requiring_maintenance ?? 0,
            'damaged_items' => $this->damaged_items ?? 0,
        ];

        return $return;
    }
}
