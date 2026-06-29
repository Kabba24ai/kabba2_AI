<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Services
use App\Services\Equipment\EquipmentStatusService;
use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
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

        $order = Order::with([
                'products.checklistQuestions.answers',
                'products.deliveryMedia.media',
                'products.deliverySignatureMedia',
            ])
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
                EquipmentStatusService::markAvailableOnChecklistRemove(
                    $equipment,
                    $orderProduct->order_id,
                    $orderProduct->id,
                    auth('api_user')->id()
                );
            }

            $orderProduct->checklistQuestions()->delete();

            // Delete delivery photo files. deliveryMedia is a hasMany(OrderMedia) collection;
            // forceDelete() on each item triggers the OrderMedia boot hook which calls
            // MediaHelper::removeFile($model->media) and deletes the underlying Media record.
            foreach ($orderProduct->deliveryMedia as $orderMedia) {
                $orderMedia->forceDelete();
            }

            // Delete the delivery signature file. deliverySignatureMedia is a belongsTo(Media)
            // single model — MediaHelper::removeFile() deletes the storage file and the Media row.
            if ($orderProduct->delivery_signature_media_id && $orderProduct->deliverySignatureMedia) {
                MediaHelper::removeFile($orderProduct->deliverySignatureMedia);
            }

            $orderProduct->update($orderProductData);
        }

        // fire event
        $user = auth('api_user')->user();
        $type = 'checklist_removed';
        event(new OrderCustomerChecklistEvent($order, $user, $type));
        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_removed_successfully'),
        ]);
    }
}
