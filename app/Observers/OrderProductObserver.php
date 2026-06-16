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
        if (ConfigurationHelper::getSettings('Schedule Assignment', 'auto_assign_enabled') !== '1') {
            return;
        }

        $productType = data_get($orderProduct->product_data, 'product_type');
        if ($productType !== 'Rental' || empty($orderProduct->delivery_date)) {
            return;
        }

        if (
            $orderProduct->delivery_status === 'Reschedule' ||
            $orderProduct->pickup_status   === 'Reschedule'
        ) {
            return;
        }

        $result = app(AutoAssignDirectService::class)->assignSingle($orderProduct);

        if ($result['status'] === 'assigned') {
            Log::info('Auto-assign: assigned on order product create', [
                'order_product_id' => $orderProduct->id,
                'equipment_name'   => $result['equipment_name'],
                'equipment_id'     => $result['equipment_id'],
            ]);
        }
    }
}
