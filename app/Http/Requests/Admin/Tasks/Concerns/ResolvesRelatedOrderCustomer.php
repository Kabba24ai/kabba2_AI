<?php

namespace App\Http\Requests\Admin\Tasks\Concerns;

use App\Models\Orders\Order;

/**
 * Canonical customer resolution for order-linked tasks: when a task is
 * related to an order, the order's customer_id is authoritative — a
 * mismatched related_customer_id from the browser is silently corrected
 * server-side. When the order carries no customer link, a submitted
 * customer is kept (there is nothing to mismatch against).
 */
trait ResolvesRelatedOrderCustomer
{
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if (!empty($data['related_order_id'])) {
            $orderCustomerId = Order::whereKey($data['related_order_id'])->value('customer_id');

            if ($orderCustomerId !== null) {
                $data['related_customer_id'] = $orderCustomerId;
            }
        }

        return $key === null ? $data : data_get($data, $key, $default);
    }
}
