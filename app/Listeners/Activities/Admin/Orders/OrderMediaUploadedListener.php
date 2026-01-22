<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderMediaUploadedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderMediaType;

class OrderMediaUploadedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderMediaUploadedEvent $event)
    {
        $order = $event->order;
        $type = $event->type;
        $user = $event->employee;

        $typeAction = match ($type) {
            OrderMediaType::LICENSE => OrderHistoryAction::LicenseUploaded,
            OrderMediaType::DELIVERY => OrderHistoryAction::DeliveryMediaUploaded,
            OrderMediaType::PICKUP => OrderHistoryAction::ReturnMediaUploaded,
            default => null,
        };

        $message = match ($type) {
            OrderMediaType::LICENSE => "{$user->full_name} uploaded a license document.",
            OrderMediaType::DELIVERY => "{$user->full_name} uploaded delivery video.",
            OrderMediaType::PICKUP => "{$user->full_name} uploaded return video.",
            default => "{$user->full_name} uploaded media.",
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
