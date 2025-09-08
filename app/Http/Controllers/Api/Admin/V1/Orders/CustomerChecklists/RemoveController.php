<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Enums
use App\Enums\Equipments\EquipmentCurrentStatus;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists\RemoveRequest;

// Model
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;

class RemoveController extends BaseController
{
    /**
     * Order Checklist Remove
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(RemoveRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = Order::with(['products.checklistQuestions.answers', 'products.deliveryMedia'])
            ->where('unique_id', $validated['order_unique_id'])
            ->first();

        if (!$order) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.order_not_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $orderProductData = [
            'delivery_by' => null,
            'delivery_notes' => null,
            'delivery_signature_media_id' => null,
            'delivery_status' => 'Pending',
            'is_delivered' => false,
            'is_returned' => false,
            'start_hours' => null,
            'equipment_id' => null,
            'equipment_details' => null,
            'assigned_by' => null,
            'assigned_at' => null,
        ];

        foreach ($order->products as $orderProduct) {
            if ($orderProduct->checklistQuestions->isEmpty()) {
                continue;
            }

            if ($equipment = Equipment::where('id', $orderProduct->equipment_id)->first()) {
                $equipment->current_status = EquipmentCurrentStatus::Available->value;
                $equipment->current_order_id = null;
                $equipment->current_order_product_id = null;
                $equipment->saveQuietly();
            }

            $orderProduct->checklistQuestions()->delete();

            if ($orderProduct->delivery_signature_media_id) {
                MediaHelper::removeFile($orderProduct->deliveryMedia);
            }

            $orderProduct->update($orderProductData);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_removed_successfully'),
        ]);
    }
}
