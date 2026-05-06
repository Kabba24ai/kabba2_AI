<?php

namespace App\Http\Controllers\Api\Admin\V1\CustomerChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\CustomerChecklists\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestions\ListResource;
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource as EquipmentListResource;
// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;

class IndexController extends BaseController
{
    /**
     * Customer Checklist Questions List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $equipmentUniqueId = $validated['equipment_unique_id'] ?? null;
        $orderProductUniqueId = $validated['order_product_unique_id'] ?? null;
        $type = $validated['type'];

        $questions = collect();

        if ($type == "return" && $orderProductUniqueId) {
            $orderProduct = OrderProduct::with(['checklistQuestions.answers', 'checklistQuestions.deliverySelectedAnswer', 'checklistQuestions.returnSelectedAnswer','equipment'])
                ->whereHas('order')
                ->where('unique_id', $orderProductUniqueId)
                ->first();

            $questions = optional($orderProduct)->checklistQuestions ?? collect();
            $equipment = optional($orderProduct)->equipment ?? null;
        }

        if ($type == "delivery" && $equipmentUniqueId) {
            $equipment = Equipment::query()
                ->with(['checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category'])
                ->where('unique_id', $equipmentUniqueId)
                ->first();

            if (!$equipment || !$equipment->checklistMaster?->customer_admin_template_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.customer_checklists.no_customer_checklist_found'),
                    ],
                    JsonResponse::HTTP_NOT_FOUND,
                );
            }

            $questions = optional($equipment->checklistMaster?->customerAdminTemplate?->templateQuestions)
                    ->pluck('question')   // same as map->question but clearer
                    ->filter()            // remove nulls
                    ->values() ?? collect();
        }

        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.customer_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customer_checklists.customer_checklist_found'),
            'customer_checklist_questions' => ListResource::collection($questions),
            'equipment' => new EquipmentListResource($equipment ?? null),
        ]);
    }
}
