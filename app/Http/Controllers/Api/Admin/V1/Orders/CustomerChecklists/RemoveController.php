<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

        // Wrapped in a transaction (including the event() dispatch below, since its
        // listener runs synchronously inline) so a failure anywhere in this sequence
        // rolls back every order product's checklist/equipment changes together
        // instead of leaving some products reverted and others not.
        return DB::transaction(function () use ($validated) {
            $order = Order::with([
                    'products.checklistQuestions.answers',
                    'products.deliveryMedia.media',
                    'products.deliverySignatureMedia',
                    'products.pickupMedia.media',
                    'products.returnSignatureMedia',
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

            // BUG-5 fix: the pickup/return-side fields below are the exact counterparts
            // of the delivery-side fields already reset above (pickup_by/pickup_notes/
            // pickup_signature_media_id/pickup_status/end_hours mirror delivery_by/
            // delivery_notes/delivery_signature_media_id/delivery_status/start_hours;
            // damage_status is return-only and meaningless without a completed return
            // on record). Without these, a removal after a return left is_returned=false
            // sitting alongside pickup_status='Completed', pickup_by still set, and a
            // stale damage_status — a contradictory state no valid delivery/return cycle
            // could otherwise produce. See docs/checklist-system-audit/P3_11_BUG5_RETURN_STATE_RESET.md.
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
                'pickup_by' => null,
                'pickup_notes' => null,
                'pickup_signature_media_id' => null,
                'pickup_status' => 'Pending',
                'end_hours' => null,
                'damage_status' => null,
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

                // BUG-5 fix: mirror the delivery-side media cleanup above for the pickup/
                // return side, so no orphaned pickup photo/signature files survive
                // alongside the now-cleared pickup_* fields.
                foreach ($orderProduct->pickupMedia as $orderMedia) {
                    $orderMedia->forceDelete();
                }

                if ($orderProduct->pickup_signature_media_id && $orderProduct->returnSignatureMedia) {
                    MediaHelper::removeFile($orderProduct->returnSignatureMedia);
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
        });
    }
}
