<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderPlacedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Helpers\ConfigurationHelper;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class SendCodSmsListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event)
    {
        $order = $event->order;
        $customer = $event->customer;

        $smsSetting = ConfigurationHelper::getSettings('Default Sales Funnel Settings');
        if (!$smsSetting || empty($order->billingAddress->phone)) {
            return;
        }

        if ($order->lastPayment->payment_method === OrderPaymentMethod::COD) {
            $to = $order->billingAddress->phone; // Customer's phone number


            if($firstProduct = $order->orderProducts->first()){
                if ($firstProduct->product_data['product_type'] === 'Rental' && $firstProduct->delivery_transport_mode == "Store" && !empty($smsSetting['store_delivery_cod_order_message']) && $smsSetting['store_delivery_cod_message_enabled'] == true) {
                    $message = $smsSetting['store_delivery_cod_order_message'];
                } elseif ($firstProduct->product_data['product_type'] !== 'Rental' && $firstProduct->delivery_transport_mode == "Truck" && !empty($smsSetting['truck_delivery_cod_order_message']) && $smsSetting['truck_delivery_cod_message_enabled'] == true) {
                    $message = $smsSetting['truck_delivery_cod_order_message'];
                } else {
                    Log::error('SendCodSms error: COD message template is empty', [
                        'order_unique_id' => $order->unique_id,
                    ]);
                    return;
                }
            }else{
                return;
            }

            try {
                $twilio = new TwilioService();
                $result = $twilio->sendSms($to, $message);

                if ($result['success']) {
                    $order->history()->create([
                        'customer_id' => $customer->id,
                        'user_id' => null,
                        'action_by' => OrderHistoryActionBy::System,
                        'action_date' => now(),
                        'action' => OrderHistoryAction::CodSmsNotification,
                        'description' => "COD SMS notification sent to customer.",
                    ]);
                } else {
                    Log::error('SendCodSms error', [
                        'order_unique_id' => $order->unique_id,
                        'response' => $result,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('SendCodSms error', [
                    'order_unique_id' => $order->unique_id,
                    'exception' => $e,
                ]);
            }
        }
    }
}
