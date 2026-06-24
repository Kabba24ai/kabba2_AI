<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Services\TwilioService;

class TermsService
{
    public function sendTermsRequest(Order $order, int $sendNumber, string $source = 'automatic')
    {
        $column = match ($sendNumber) {
            1 => 'terms_first_sent_at',
            2 => 'terms_second_sent_at',
            3 => 'terms_third_sent_at',
        };
        $messageIdColumn = match ($sendNumber) {
            1 => 'terms_first_message_id',
            2 => 'terms_second_message_id',
            3 => 'terms_third_message_id',
        };
        // Do not send if this order is related to another order
        if (!empty($order->reference_order_number)) {
            return;
        }
        if ($order->{$column}) {
            return;
        }
        $customerName = $order->billingAddress->full_name ?? 'Customer';
        $orderNumber = $order->order_number;
        $termsUrl = route('front.terms-and-conditions.index', $order->unique_id);
        $messages = [
            1 => "Hi {$customerName}, please review and sign the rental terms for your order here: {$termsUrl} Thank you!",
            2 => "Reminder: your rental terms still need to be signed for order {$orderNumber}. Please complete them here: {$termsUrl} Thank you!",
            3 => "Final reminder for today’s rental: please sign your rental terms before delivery/pickup here: {$termsUrl} Thank you!",
        ];
        $message = $messages[$sendNumber];
        $twilio = new TwilioService();
        $result = $twilio->sendSms($order->billingAddress->phone, $message, [], [
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'sms_type'    => 'terms_and_conditions',
            'phone'       => $order->billingAddress->phone,
            'message'     => $message,
        ]);
        $order->update([
            $column => now(),
            $messageIdColumn => $result['sid'] ?? null,
        ]);

        // Add to order history
        $action = match ($sendNumber) {
            1 => \App\Enums\Orders\OrderHistoryAction::TermsFirstRequest,
            2 => \App\Enums\Orders\OrderHistoryAction::TermsSecondRequest,
            3 => \App\Enums\Orders\OrderHistoryAction::TermsThirdRequest,
        };
        $historyDescription = match ($sendNumber) {
            1 => 'Terms request SMS sent.',
            2, 3 => 'Terms reminder SMS sent.',
        };
        $order->history()->create([
            'customer_id' => $order->customer_id,
            'user_id' => null,
            'action_date' => now(),
            'action_by' => \App\Enums\Orders\OrderHistoryActionBy::System,
            'action' => $action,
            'description' => $historyDescription,
        ]);
    }
}
