<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderCustomerChecklistEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderCustomerChecklistType;

class OrderCustomerChecklistListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderCustomerChecklistEvent $event)
    {
        $order = $event->order;
        $user = $event->employee;
        $type = $event->type;

        $typeEnum = OrderCustomerChecklistType::from($type);

        $typeAction = match ($typeEnum) {
            OrderCustomerChecklistType::ChecklistDelivery => OrderHistoryAction::ChecklistDelivered,
            OrderCustomerChecklistType::ChecklistReturn => OrderHistoryAction::ChecklistReturned,
            OrderCustomerChecklistType::ChecklistRemoved => OrderHistoryAction::ChecklistRemoved,
            default => null,
        };

        $message = match ($type) {
            OrderCustomerChecklistType::ChecklistDelivery => "{$user->full_name} delivery checklist filled and machine delivered.",
            OrderCustomerChecklistType::ChecklistReturn => "{$user->full_name} return checklist filled and machine returned.",
            OrderCustomerChecklistType::ChecklistRemoved => "{$user->full_name} removed the checklist from the order.",
        };

        $order->history()->create([
            'customer_id' => null,
            'user_id' => $user?->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $typeAction,
            'description' => $message,
        ]);
    }
}
