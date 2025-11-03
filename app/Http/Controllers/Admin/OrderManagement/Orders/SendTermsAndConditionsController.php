<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

use App\Models\Orders\Order;
use App\Services\TwilioService;

class SendTermsAndConditionsController extends Controller
{
	/**
	 * Send Terms & Conditions link/text to the order's shipping phone number via Twilio.
	 */
	public function __invoke($orderUniqueId)
	{
		$response = null;

		$order = Order::with(['shippingAddress'])->where('unique_id', $orderUniqueId)->where('terms_status', 'Pending')->first();
		if (!$order) {
			$response = response()->json(['success' => false, 'message' => 'Order not found.'], 404);
		}else{
            $shippingPhone = $order->shippingAddress->phone ?? null;
            if (!$shippingPhone) {
                $response = response()->json(['success' => false, 'message' => 'Shipping phone number not available for this order.'], 422);
            }else{
                $termsUrl = route('front.terms-and-conditions.index', $order->unique_id);
                $customerName = $order->shippingAddress->full_name;
                $orderNumber = $order->order_number;

                $message = "Hello {$customerName},\n\nPlease click the link to sign the Terms & Conditions for your rental order: {$orderNumber}: {$termsUrl}";

                try {
                    $twilio = new TwilioService();
                    $result = $twilio->sendSms($shippingPhone, $message);

                    if ($result['success']) {
                        $response = response()->json(['success' => true, 'message' => 'Terms & Conditions message sent successfully.'], 200);
                    } else {
                        $response = response()->json(['success' => false, 'message' => 'Failed to send Terms & Conditions message: ' . ($result['message'] ?? 'Unknown error')], 500);
                    }
                } catch (\Throwable $e) {
                    Log::error('SendTermsAndConditions error', [
                        'order_unique_id' => $order->unique_id,
                        'exception' => $e,
                    ]);
                    $response = response()->json(['success' => false, 'message' => 'An internal error occurred while sending message.'], 500);
                }
            }
        }

		return $response;
	}
}

