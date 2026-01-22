<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderNoteEvent;

// enums
use App\Enums\Orders\OrderAddressType;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderNoteListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderNoteEvent $event)
    {
        $order = $event->order;
        $user = $event->employee;
        $type = $event->type;
        $note = $event->note;

        $typeAction = match ($type) {
            "created" => OrderHistoryAction::NoteCreated,
            "updated" => OrderHistoryAction::NoteUpdated,
            "deleted" => OrderHistoryAction::NoteDeleted,
            default => null,
        };

        $message = match ($type) {
            "created" => "{$user->full_name} created a note.",
            "updated" => "{$user->full_name} updated a note.",
            "deleted" => "{$user->full_name} deleted a note.",
            default => "{$user->full_name} updated a note.",
        };

        $order->history()->create([
            'customer_id' => null,
            'user_id' => $user?->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $typeAction,
            'description' => $message,
            'extras' => json_encode($note->toArray()),
        ]);
    }
}
