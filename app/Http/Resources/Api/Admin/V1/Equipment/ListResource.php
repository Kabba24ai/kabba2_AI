<?php

namespace App\Http\Resources\Api\Admin\V1\Equipment;

use App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestions\ListResource as CustomerChecklistQuestionsListResource;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource as OrderProductsListResource;
use App\Http\Resources\Api\Admin\V1\ProductCategories\ListResource as ProductCategoriesListResource;
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
            'equipment_name' => $this->equipment_name ?? '',
            'product_category_id' => $this->product_category_id ?? 0,
            'equipment_id' => $this->equipment_id ?? 0,
            'equipment_hours' => $this->equipment_hours ?? 0,

            'is_tracked' => $this->is_tracked ?? '',

            'overage_rate' => $this->overage_rate ?? 0,
            'brand' => $this->brand ?? '',
            'model' => $this->model ?? '',
            'model_year' => $this->model_year ?? '',
            'date_acquired' => $this->date_acquired ?? '',
            'purchase_cost' => $this->purchase_cost ?? 0,
            'ownership_type' => $this->ownership_type ?? '',
            'finance_company' => $this->finance_company ?? '',
            'term_in_months' => $this->term_in_months ?? 0,
            'interest_rate' => $this->interest_rate ?? 0,
            'monthly_payment' => $this->monthly_payment ?? 0,
            'vehicle_identification_number' => $this->vehicle_identification_number ?? '',
            'serial_number' => $this->serial_number ?? '',
            'license_plate' => $this->license_plate ?? '',
            'imei' => $this->imei ?? '',
            'power_source_type' => $this->power_source_type ?? '',
            'has_def' => $this->has_def ?? '',
            'diesel_tank_capacity' => $this->diesel_tank_capacity ?? 0,
            'def_tank_capacity' => $this->def_tank_capacity ?? 0,
            'gas_tank_capacity' => $this->gas_tank_capacity ?? 0,
            'standard_battery_count' => $this->standard_battery_count ?? 0,
            'expanded_battery_count' => $this->expanded_battery_count ?? 0,
            'checklist_master_id' => $this->checklist_master_id ?? 0,
            'equipment_notes' => $this->equipment_notes ?? '',
            'current_status' => $this?->current_status->label() ?? '',
            'product_category' => new ProductCategoriesListResource($this->whenLoaded('productCategory') ?? []),

            'current_order_id' => $this->current_order_id ?? 0,
            'current_order_product_id' => $this->current_order_product_id ?? 0,

            'order_product' => new OrderProductsListResource($this->whenLoaded('orderProduct')),

            'checklist_qas' => CustomerChecklistQuestionsListResource::collection($this->whenLoaded('checklistQA')),

        ];

        return $return;
    }
}
