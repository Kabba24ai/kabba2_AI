<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderExtraChargeEvent;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderExtraChargeListener
{
    public function handle(OrderExtraChargeEvent $event)
    {
        $order  = $event->order;
        $charge = $event->charge; 
        $user   = $event->employee;
        $action = $event->action;

        $historyAction = match ($action) {
            'collected'     => OrderHistoryAction::PaymentCollected,
            'uncollectable' => OrderHistoryAction::PaymentUncollectable,
            default         => OrderHistoryAction::PaymentCollected,
        };

        $typeLabel = ucfirst($charge?->type ?? 'Charge');

        $message = match ($action) {
            'collected' =>
                "{$user->full_name} collected {$typeLabel} payment of $" .
                number_format($charge->amount ?? 0, 2),

            'uncollectable' =>
                "{$user->full_name} marked {$typeLabel} charge as uncollectable",

            default =>
                "{$user->full_name} updated {$typeLabel} payment",
        };

        $order->history()->create([
            'customer_id' => $charge?->customer_id ?? $order->customer_id,
            'user_id'     => $user->id,
            'action_by'   => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action'      => $historyAction,
            'description' => $message,
            'extras'      => json_encode(
                $charge?->toArray() ?? [
                    'action' => 'uncollectable',
                ]
            ),
        ]);
    }

}
