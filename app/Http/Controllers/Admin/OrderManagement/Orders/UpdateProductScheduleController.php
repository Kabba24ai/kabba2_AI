<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

// Base Controller

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;

// Events
use App\Events\Admin\Orders\OrderProductScheduleUpdated;

// Models
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductChecklistQuestionAnswers;

// Request
use App\Http\Requests\Admin\OrderManagement\Orders\UpdateProductScheduleRequest;

class UpdateProductScheduleController extends Controller
{
    /**
     * Handle updating of a product's delivery/return schedule.
     */
    public function __invoke(UpdateProductScheduleRequest $request, $orderUniqueId, $orderProductUniqueId)
    {
        // Validate order product exists
        $validatedData = $request->validated();

        $orderProduct = OrderProduct::whereHas('order')
            ->where('unique_id', $orderProductUniqueId)
            ->first();
        if (!$orderProduct) {
            return response()->json(['message' => 'Order product not found.'], 404);
        }
        $user = auth()->user();

        $deliveryChanged = false;
        $pickupChanged = false;
        $storeChange = false;
        $deliveryStatusChanged = false;
        $pickupStatusChanged = false;

        // Only update the single field passed for delivery or pickup/return using $validatedData
        if ($validatedData['type'] === 'delivery') {

            $deliveryFields = [
                'delivery_date', 'delivery_time', 'delivery_transport_mode', 'delivery_status', 'delivery_store_id', 'delivery_by'
            ];
            foreach ($deliveryFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $orderProduct->$field = $validatedData[$field];
                    // Track if mode was changed
                    if ($field === 'delivery_transport_mode') {
                        $deliveryChanged = true;
                    }

                    if ($field === 'delivery_status') {
                        $deliveryStatusChanged = true;
                    }

                    // Lock logic for delivery_by
                    if ($field === 'delivery_by') {
                        if (empty($validatedData[$field])) {
                            // Cleared — release all delivery locks
                            $orderProduct->delivery_driver_locked   = false;
                            $orderProduct->delivery_priority_locked = false;
                        } else {
                            // Assigned (new or reassigned) — clear then lock driver
                            $orderProduct->delivery_driver_locked   = true;
                            $orderProduct->delivery_priority_locked = false;
                        }
                    }

                    break;
                }
            }
        }

        if ($validatedData['type'] === 'return') {
            $pickupFields = [
                'pickup_date', 'pickup_time', 'pickup_transport_mode', 'pickup_status', 'pickup_store_id', 'pickup_by'
            ];
            foreach ($pickupFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $orderProduct->$field = $validatedData[$field];
                    //
                    if ($field === 'pickup_store_id') {
                        $storeChange = true;
                    }
                    // Track if mode was changed
                    if ($field === 'pickup_transport_mode') {
                        $pickupChanged = true;
                    }

                    if ($field === 'pickup_status') {
                        $pickupStatusChanged = true;
                    }

                    // Lock logic for pickup_by
                    if ($field === 'pickup_by') {
                        if (empty($validatedData[$field])) {
                            // Cleared — release all return locks
                            $orderProduct->pickup_driver_locked   = false;
                            $orderProduct->pickup_priority_locked = false;
                        } else {
                            // Assigned (new or reassigned) — clear then lock driver
                            $orderProduct->pickup_driver_locked   = true;
                            $orderProduct->pickup_priority_locked = false;
                        }
                    }

                    break;
                }
            }
        }

        // Auto-clear Reschedule status when a new date is assigned.
        // This returns the item to the active schedule without requiring a manual status change.
        if (array_key_exists('delivery_date', $validatedData) && $validatedData['delivery_date']) {
            if ($orderProduct->delivery_status === 'Reschedule') {
                $orderProduct->delivery_status = 'Pending';
            }
        }
        if (array_key_exists('pickup_date', $validatedData) && $validatedData['pickup_date']) {
            if ($orderProduct->pickup_status === 'Reschedule') {
                $orderProduct->pickup_status = 'Pending';
            }
        }

        // If either transport mode changed, update service_method and service_option
        if ($deliveryChanged || $pickupChanged) {
            $serviceData = $orderProduct->getServiceMethodFromTransportMode();
            $orderProduct->service_method = $serviceData['service_method'];
            $orderProduct->service_option = $serviceData['service_option'];
        }

        if ($deliveryStatusChanged || $pickupStatusChanged) {
            $equipment = $orderProduct->equipment;
            // If status changed to Completed, set completed_at timestamp
            if ($deliveryStatusChanged) {
                // $orderProduct->delivery_date = now()->format('Y-m-d');
                // $orderProduct->delivery_time = now()->format('H:i');
                if($orderProduct->delivery_status === 'Completed'){
                    $orderProduct->is_delivered = true;
                    if($equipment){
                        $beforeStatus = $equipment->current_status?->value;
                        $equipment->current_status = EquipmentCurrentStatus::Rented->value;
                        $equipment->current_status_updated_by = $user->id;
                        $equipment->current_status_changed_at = now();
                        $equipment->saveQuietly();
                        EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Rented->value, $user->id);
                    }
                }else if($orderProduct->delivery_status === 'Close as Completed'){
                    // INTENTIONALLY CHECKLIST-EXEMPT — do not "fix" this by requiring or
                    // generating a checklist here. "Close as Completed" is a deliberate
                    // admin-only status letting staff administratively close an order
                    // product (cancelled bookings, corrections, orders that never
                    // physically shipped) WITHOUT the customer delivery/return checklist
                    // workflow ever running. It unconditionally sets is_delivered=true,
                    // is_returned=true, and pickup_status='Completed' with no checklist
                    // rows created — that is correct, by-design behavior, not a bug.
                    //
                    // Investigated and confirmed in ISSUE5_INVESTIGATION_FINDINGS.md:
                    // this single branch (plus its 'Completed'-status sibling below)
                    // accounts for 831 of 875 (95%) "delivered order products with zero
                    // checklist rows" found during the checklist-system audit. See also
                    // docs/checklist-system-audit/CHECKLIST_EXEMPT_ADMIN_CLOSURE.md for
                    // the full rationale and reporting guidance this implies.
                    //
                    // Practical implication for anyone touching order/checklist reporting,
                    // billing reconciliation, or delivery dashboards: "has a checklist" is
                    // NOT a valid proxy for "was physically delivered to the customer."
                    // ~31% of delivered rental order products are administratively closed
                    // via this path, not delivered via the checklist. Distinguish the two
                    // (e.g. by checking delivery_status/pickup_status for the literal
                    // 'Close as Completed' value, or by checking whether checklist rows
                    // actually exist) rather than assuming one implies the other.
                    $orderProduct->is_delivered = true;
                    $orderProduct->is_returned = true;
                    $orderProduct->pickup_status = 'Completed';
                    if($equipment){
                        $beforeStatus = $equipment->current_status?->value;
                        $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
                        $equipment->current_status_updated_by = $user->id;
                        $equipment->current_status_changed_at = now();
                        $equipment->current_order_id = null;
                        $equipment->current_order_product_id = null;
                        $equipment->saveQuietly();
                        EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Maintenance->value, $user->id);
                    }

                }
                else if($orderProduct->delivery_status === 'Reschedule'){

                    $orderProduct->softAssignment()->delete();

                    if($equipment){
                        $beforeStatus = $equipment->current_status?->value;
                        $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
                        $equipment->current_status_updated_by = $user->id;
                        $equipment->current_status_changed_at = now();
                        $equipment->current_order_id = null;
                        $equipment->current_order_product_id = null;
                        $equipment->saveQuietly();
                        EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Maintenance->value, $user->id);
                    }

                    // BUG-3 (P3-12A) fix: soft-delete the child answer rows before their
                    // parent questions — this is a pure removal with no rebuild, so without
                    // this the answers would be permanently orphaned. See
                    // docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md.
                    $staleQuestionIds = $orderProduct->checklistQuestions()->pluck('id');
                    OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $staleQuestionIds)->delete();

                    $orderProduct->checklistQuestions()->delete();
                    $orderProduct->is_delivered = false;
                    $orderProduct->is_returned  = false;

                    // Clear all delivery-side assignments and locks
                    $orderProduct->delivery_by             = null;
                    $orderProduct->delivery_priority       = null;
                    $orderProduct->delivery_driver_locked  = false;
                    $orderProduct->delivery_priority_locked = false;
                    $orderProduct->start_hours             = null;
                    $orderProduct->equipment_id            = null;
                    $orderProduct->equipment_details       = null;
                    $orderProduct->assigned_by             = null;
                    $orderProduct->assigned_at             = null;

                    // Cascade reschedule to return: delivery never happened so the
                    // entire rental timeline is invalid until re-rescheduled.
                    $orderProduct->pickup_status           = 'Reschedule';
                    $orderProduct->pickup_by               = null;
                    $orderProduct->pickup_priority         = null;
                    $orderProduct->pickup_driver_locked    = false;
                    $orderProduct->pickup_priority_locked  = false;
                }else if($orderProduct->delivery_status === 'Pending'){
                    if($equipment){
                        $orderProduct->softAssignment()->delete();
                        $orderProduct->softAssignment()->create([
                            'equipment_id' => $equipment->id,
                            'order_id' => $orderProduct->order_id,
                            'assigned_by' => $user->id,
                        ]);

                        $beforeStatus = $equipment->current_status?->value;
                        $equipment->current_status = EquipmentCurrentStatus::Available->value;
                        $equipment->current_status_updated_by = $user->id;
                        $equipment->current_status_changed_at = now();
                        $equipment->current_order_id = null;
                        $equipment->current_order_product_id = null;
                        $equipment->saveQuietly();
                        EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Available->value, $user->id);
                    }

                    // BUG-3 (P3-12A) fix: soft-delete the child answer rows before their
                    // parent questions — this is a pure removal with no rebuild, so without
                    // this the answers would be permanently orphaned. See
                    // docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md.
                    $staleQuestionIds = $orderProduct->checklistQuestions()->pluck('id');
                    OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $staleQuestionIds)->delete();

                    $orderProduct->checklistQuestions()->delete();
                    $orderProduct->equipment_id = null;
                    $orderProduct->equipment_details = null;
                    $orderProduct->assigned_by = null;
                    $orderProduct->assigned_at = null;
                    $orderProduct->delivery_by = null;
                    $orderProduct->is_delivered = false;
                    $orderProduct->is_returned = false;
                    $orderProduct->pickup_status = 'Pending';
                }else{
                    $orderProduct->is_delivered = false;
                    $orderProduct->is_returned = false;
                    $orderProduct->pickup_status = 'Pending';
                }

            }

            if ($pickupStatusChanged) {
                // $orderProduct->pickup_date = now()->format('Y-m-d');
                // $orderProduct->pickup_time = now()->format('H:i');
                if($orderProduct->pickup_status === 'Completed' || $orderProduct->pickup_status === 'Close as Completed'){
                    $orderProduct->is_returned = true;
                    if($equipment){
                        $beforeStatus = $equipment->current_status?->value;
                        $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
                        $equipment->current_status_updated_by = $user->id;
                        $equipment->current_status_changed_at = now();
                        if($orderProduct->pickup_store_id){
                            $equipment->store_id = $orderProduct->pickup_store_id;
                        }
                        $equipment->saveQuietly();
                        EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Maintenance->value, $user->id);
                    }
                } elseif ($orderProduct->pickup_status === 'Reschedule') {
                    // Clear all return-side assignments and locks
                    $orderProduct->is_returned             = false;
                    $orderProduct->pickup_by               = null;
                    $orderProduct->pickup_priority         = null;
                    $orderProduct->pickup_driver_locked    = false;
                    $orderProduct->pickup_priority_locked  = false;
                } else {
                    $orderProduct->is_returned = false;
                }

            }
        }

        // Optionally update allocated_hours if provided
        if (array_key_exists('allocated_hours', $validatedData) && $validatedData['allocated_hours'] !== null) {
            $orderProduct->allocated_hours = $validatedData['allocated_hours'];
        }

        $orderProduct->save();

        if ($storeChange) {
            $equipment = $orderProduct->equipment;
            if ($equipment) {
                $equipment->store_id = $validatedData['pickup_store_id'];
                $equipment->saveQuietly();
            }
        }

        // Fire an event for the updated schedule
        $data = [
            'requested_data' => $validatedData,
            'order_product' => $orderProduct->toArray(),
        ];

        event(new OrderProductScheduleUpdated($orderProduct->order, $user, $data));

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.'
        ]);
    }
}
