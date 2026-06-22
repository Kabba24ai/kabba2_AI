<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Enums\Communication\SmsType;
use App\Events\Front\Checkout\OrderPlacedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Helpers\ConfigurationHelper;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class SendSmsListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event)
    {
        $order    = $event->order;
        $customer = $event->customer;

        $phone         = $order->billingAddress->phone ?? null;
        $paymentMethod = $order->lastPayment->payment_method ?? null;

        Log::info('[Checkout SMS] Listener triggered', [
            'order_id'       => $order->unique_id,
            'order_number'   => $order->order_number ?? null,
            'customer_id'    => $customer->id ?? null,
            'customer_name'  => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'customer_email' => $customer->email ?? null,
            'to_phone'       => $phone,
            'payment_method' => $paymentMethod instanceof \BackedEnum ? $paymentMethod->value : $paymentMethod,
        ]);

        $smsSetting = ConfigurationHelper::getSettings('Default Sales Funnel Settings');
        if (!$smsSetting) {
            Log::warning('[Checkout SMS] No "Default Sales Funnel Settings" found — SMS skipped', [
                'order_id' => $order->unique_id,
            ]);
            return;
        }

        if (empty($phone)) {
            Log::warning('[Checkout SMS] Billing address has no phone number — SMS skipped', [
                'order_id'    => $order->unique_id,
                'customer_id' => $customer->id ?? null,
            ]);
            return;
        }

        $firstProduct = optional($order->products)->first();
        if (!$firstProduct) {
            Log::warning('[Checkout SMS] Order has no products — SMS skipped', [
                'order_id' => $order->unique_id,
            ]);
            return;
        }

        $isRental = $firstProduct->product_data['product_type'] === 'Rental';
        $isStore  = $firstProduct->delivery_transport_mode === "Store";
        $isTruck  = $firstProduct->delivery_transport_mode === "Truck";

        Log::info('[Checkout SMS] Order details resolved', [
            'order_id'      => $order->unique_id,
            'product_type'  => $firstProduct->product_data['product_type'] ?? null,
            'delivery_mode' => $firstProduct->delivery_transport_mode ?? null,
            'is_rental'     => $isRental,
            'is_store'      => $isStore,
            'is_truck'      => $isTruck,
        ]);

        $message  = null;
        $smsType  = null;
        $template = null;

        if ($paymentMethod === OrderPaymentMethod::COD) {
            if ($isRental && $isStore && !empty($smsSetting['store_delivery_cod_order_message']) && $smsSetting['store_delivery_cod_message_enabled']) {
                $message  = $smsSetting['store_delivery_cod_order_message'];
                $smsType  = SmsType::COD_ORDER_NOTIFICATION;
                $template = 'store_delivery_cod_order_message';
            } elseif (!$isRental && $isTruck && !empty($smsSetting['truck_delivery_cod_order_message']) && $smsSetting['truck_delivery_cod_message_enabled']) {
                $message  = $smsSetting['truck_delivery_cod_order_message'];
                $smsType  = SmsType::COD_ORDER_NOTIFICATION;
                $template = 'truck_delivery_cod_order_message';
            }
        } elseif ($paymentMethod === OrderPaymentMethod::Card) {
            if ($isRental && $isStore && !empty($smsSetting['store_delivery_card_order_message']) && $smsSetting['store_delivery_card_message_enabled']) {
                $message  = $smsSetting['store_delivery_card_order_message'];
                $smsType  = SmsType::CARD_ORDER_NOTIFICATION;
                $template = 'store_delivery_card_order_message';
            } elseif (!$isRental && $isTruck && !empty($smsSetting['truck_delivery_card_order_message']) && $smsSetting['truck_delivery_card_message_enabled']) {
                $message  = $smsSetting['truck_delivery_card_order_message'];
                $smsType  = SmsType::CARD_ORDER_NOTIFICATION;
                $template = 'truck_delivery_card_order_message';
            }
        }

        if (empty($message)) {
            Log::warning('[Checkout SMS] No matching enabled message template — SMS skipped', [
                'order_id'       => $order->unique_id,
                'payment_method' => $paymentMethod instanceof \BackedEnum ? $paymentMethod->value : $paymentMethod,
                'is_rental'      => $isRental,
                'is_store'       => $isStore,
                'is_truck'       => $isTruck,
                'hint'           => 'Check that the matching template is filled in and the enabled toggle is ON in settings.',
            ]);
            return;
        }

        Log::info('[Checkout SMS] Sending SMS', [
            'order_id'       => $order->unique_id,
            'order_number'   => $order->order_number ?? null,
            'sms_type'       => $smsType->value,
            'template_key'   => $template,
            'to_phone'       => $phone,
            'customer_id'    => $customer->id ?? null,
            'customer_name'  => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'customer_email' => $customer->email ?? null,
            'message'        => $message,
        ]);

        try {
            $twilio = new TwilioService();
            $result = $twilio->sendSms($phone, $message, [], [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'sms_type'    => $smsType,
            ]);

            if ($result['success']) {
                Log::info('[Checkout SMS] SMS sent successfully', [
                    'order_id'    => $order->unique_id,
                    'sms_type'    => $smsType->value,
                    'to_phone'    => $result['to'] ?? $phone,
                    'from_phone'  => $result['from'] ?? null,
                    'twilio_sid'  => $result['sid'] ?? null,
                ]);

                $order->history()->create([
                    'customer_id' => $customer->id,
                    'user_id'     => null,
                    'action_by'   => OrderHistoryActionBy::System,
                    'action_date' => now(),
                    'action'      => OrderHistoryAction::CodSmsNotification,
                    'description' => "Order SMS notification ({$smsType->value}) sent to {$phone}.",
                ]);
            } else {
                Log::error('[Checkout SMS] Twilio returned failure', [
                    'order_id'    => $order->unique_id,
                    'sms_type'    => $smsType->value,
                    'to_phone'    => $phone,
                    'response'    => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[Checkout SMS] Exception while sending SMS', [
                'order_id'    => $order->unique_id,
                'sms_type'    => $smsType->value ?? null,
                'to_phone'    => $phone,
                'customer_id' => $customer->id ?? null,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
