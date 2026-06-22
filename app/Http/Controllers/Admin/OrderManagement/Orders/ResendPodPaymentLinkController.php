<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\PodPaymentLinkEvent;
use App\Enums\Orders\PodPaymentLinkStatus;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\PodPaymentLink;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResendPodPaymentLinkController extends Controller
{
    public function __invoke($uniqueId)
    {
        $order = Order::with(['customer', 'payments', 'billingAddress'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        // Only for COD orders with a pending payment
        $hasCodPending = $order->payments
            ->where('payment_method', 'COD')
            ->where('status', 'Pending')
            ->isNotEmpty();

        if (!$hasCodPending) {
            return response()->json(['success' => false, 'message' => 'This order does not have a pending COD payment.'], 422);
        }

        // Resolve phone — customer profile first, then billing address
        $phone = $order->customer?->phone ?? $order->billingAddress?->phone ?? null;

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'No phone number found for this customer.'], 422);
        }

        // Load message template from settings (reuse reminder #1 template)
        $settings        = ConfigurationHelper::getSettings('Default Sales Funnel Settings');
        $messageTemplate = $settings['pod_payment_reminder_1_message'] ?? null;

        $paymentLink = route('front.checkout.order-payment-form', [
            'order' => encrypt($order->unique_id),
        ]);

        $message = $messageTemplate
            ? str_replace('{{payment_link}}', $paymentLink, $messageTemplate)
            : "Rent 'n King: Your rental order {$order->order_number} has a pending payment. Pay here to confirm your reservation: {$paymentLink}";

        try {
            $twilio = new TwilioService();

            // Ensure tracker row exists
            $podLink = PodPaymentLink::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_link_token'      => Str::uuid()->toString(),
                    'pod_status'              => PodPaymentLinkStatus::Pending->value,
                    'payment_link_created_at' => now(),
                ]
            );

            if ($podLink->wasRecentlyCreated) {
                $podLink->activities()->create([
                    'order_id' => $order->id,
                    'event'    => PodPaymentLinkEvent::LinkCreated->value,
                    'metadata' => ['triggered_by' => 'admin_manual_resend'],
                ]);
            }

            $result = $twilio->sendSms($phone, $message, [], [
                'order_id'            => $order->id,
                'customer_id'         => $order->customer_id,
                'sms_type'            => SmsType::POD_PAYMENT_LINK_MANUAL_RESEND->value,
                'pod_payment_link_id' => $podLink->id,
            ]);

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS failed: ' . ($result['message'] ?? 'Unknown error'),
                ], 500);
            }

            // Record the manual resend event
            $podLink->recordEvent(PodPaymentLinkEvent::ManualResend, [
                'twilio_sid'    => $result['sid'] ?? null,
                'phone'         => $phone,
                'triggered_by'  => auth()->id(),
            ]);

            Log::channel('jobs')->info('[POD Manual Resend] Payment link sent by admin', [
                'order_id'    => $order->unique_id,
                'admin_id'    => auth()->id(),
                'twilio_sid'  => $result['sid'] ?? null,
                'phone'       => $phone,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Payment link sent successfully.',
                'twilio_sid' => $result['sid'] ?? null,
                'sent_at'    => now()->format('m/d/Y h:i A'),
            ]);

        } catch (\Throwable $e) {
            Log::error('[POD Manual Resend] Exception', [
                'order_id'  => $order->unique_id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
