<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderPlacedEvent;
use App\Services\FirebaseService;

class SendAdminNotificationListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event)
    {
        $order = $event->order;

        // Send admin notification logic here
        $notification = new FirebaseService();
        $title = 'New Order Placed';
        $body = 'Order ' . $order->order_number . ' has been placed.';
        $notification->sendToAllDevices($title, $body, [
            'order_id' => (string)$order->id,
            'order_unique_id' => (string)$order->unique_id,
            'order_number' => (string)$order->order_number,
        ]);
    }
}
