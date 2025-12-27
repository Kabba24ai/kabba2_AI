<?php

namespace App\Http\Controllers\Api\Admin\V1\Equipment;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Equipment\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;

class IndexController extends BaseController
{
    /**
     * Equipment List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = $validated['type'] ?? null;

        // that is use for the ordering of the equipment based on the type
        if($type === 'RentalReady'){
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }else if($type === 'Checklist'){
            $order = ['available', 'rented', 'maintenance', 'damaged'];
        }else{
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }

        $equipment = Equipment::with(['productCategory', 'orderProduct','checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category','orderProduct.checklistQuestions.answers', 'orderProduct.checklistQuestions.deliverySelectedAnswer', 'orderProduct.checklistQuestions.returnSelectedAnswer', 'softAssignments', 'store','checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','checklistMaster.rentalReadyTemplate.templateQuestions.question.category', 'orderProduct.equipmentRentalReadyTemplate.checklistQuestions'])
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')") // order by current_status based on the defined order
            ->orderBy('equipment_name', 'ASC')
            ->get();


        $equipment->map(function($item) {
            $isRentedAndDelivered = $item->current_status->isRented()
                && $item->orderProduct
                && ($item->orderProduct->is_delivered == 1);

            if ($isRentedAndDelivered) {
                $questions = optional($item->orderProduct->checklistQuestions) ?? collect();

                $rentalReadyQuestions = optional($item->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions)
                            ->pluck('rental_ready_qa_json')   // same as map->question but clearer
                            ->filter()            // remove nulls
                            ->values() ?? collect();

                $rentalReadyQuestions = collect($rentalReadyQuestions)->map(function ($item) {
                                return is_string($item) ? json_decode($item, true) : $item; // decode to array
                            });
            } else {
                $templateQuestions = $item->checklistMaster?->customerAdminTemplate?->templateQuestions;
                $questions = collect($templateQuestions)
                    ->pluck('question')
                    ->filter()
                    ->values();

                $rentalReadyQuestions = $item->checklistMaster?->rentalReadyTemplate?->templateQuestions;
                $rentalReadyQuestions = collect($rentalReadyQuestions)
                    ->pluck('question')   // same as map->question but clearer
                    ->filter()            // remove nulls
                    ->values() ?? collect();

            }

            $item->setRelation('checklistQA', $questions);
            $item->setRelation('rentalReadyQA', $rentalReadyQuestions);
            return $item;
        });
        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.equipment_found'),
            'equipment' => ListResource::collection($equipment),
        ]);
    }
}
