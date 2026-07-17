<?php

namespace App\Observers;

use App\Helpers\ConfigurationHelper;
use App\Models\Orders\OrderProduct;
use App\Services\AutoAssignDirectService;
use Illuminate\Support\Facades\Log;

class OrderProductObserver
{
    public function created(OrderProduct $orderProduct): void
    {
        $this->tryAutoAssign($orderProduct, 'create');
    }

    /**
     * Also fires when delivery_date is set after initial creation (e.g. admin order editing).
     * Note: checkout orders use bulk insert() which bypasses observers entirely —
     * auto-assign for those is triggered directly in the checkout PostController.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        // Only act when delivery_date just became set (was null before) and no assignment yet.
        if (!$orderProduct->wasChanged('delivery_date') || empty($orderProduct->delivery_date)) {
            return;
        }

        if ($orderProduct->softAssignment || $orderProduct->equipment_id) {
            return;
        }

        $this->tryAutoAssign($orderProduct, 'update');
    }

    private function tryAutoAssign(OrderProduct $orderProduct, string $trigger): void
    {
        if (ConfigurationHelper::getSettings('Schedule Assignment', 'auto_assign_enabled') !== '1') {
            return;
        }

        $productType = data_get($orderProduct->product_data, 'product_type');
        if ($productType !== 'Rental' || empty($orderProduct->delivery_date)) {
            return;
        }

        // TD-6 (Phase 3 tech debt): delivery_status/pickup_status are also
        // written by the checklist Save controllers (mobile delivery/return,
        // customer/rental-ready checklists) — this auto-assign gate is
        // implicitly coupled to whatever those controllers last set these
        // fields to. Revisit if TD-1/ARCH-2 ever changes that write path. See
        // docs/checklist-system-audit/P3_5_TECHNICAL_DEBT.md.
        if (
            $orderProduct->delivery_status === 'Reschedule' ||
            $orderProduct->pickup_status   === 'Reschedule'
        ) {
            return;
        }

        $result = app(AutoAssignDirectService::class)->assignSingle($orderProduct);

        if ($result['status'] === 'assigned') {
            Log::info("Auto-assign: assigned on order product {$trigger}", [
                'order_product_id' => $orderProduct->id,
                'equipment_name'   => $result['equipment_name'],
                'equipment_id'     => $result['equipment_id'],
                'priority'         => $result['priority'],
            ]);
        }
    }
}
