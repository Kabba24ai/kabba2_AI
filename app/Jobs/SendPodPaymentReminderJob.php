<?php

namespace App\Jobs;

use App\Enums\Communication\SmsType;
use App\Helpers\ConfigurationHelper;
use App\Models\Orders\Order;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendPodPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('jobs')->{$level}($message, $context);
    }

    public function handle(): void
    {
        $startedAt = now();
        $this->log('info', '[POD Reminder] Job started at ' . $startedAt->toDateTimeString());

        // ── 1. Load & validate settings ──────────────────────────────────────
        $settings = ConfigurationHelper::getSettings('Default Sales Funnel Settings');

        if (($settings['pod_payment_reminder_1_enabled'] ?? null) != '1') {
            $this->log('info', '[POD Reminder] Reminder #1 is disabled in settings — job exiting.');
            return;
        }

        $messageTemplate = $settings['pod_payment_reminder_1_message'] ?? null;
        if (!$messageTemplate) {
            $this->log('warning', '[POD Reminder] No message template found for pod_payment_reminder_1_message — job exiting.');
            return;
        }

        $this->log('info', '[POD Reminder] Settings loaded. Message template: "' . $messageTemplate . '"');

        // ── 2. Query eligible orders ─────────────────────────────────────────
        $orders = Order::with('customer')
            ->whereHas('payments', function ($q) {
                $q->where('payment_method', 'COD')
                  ->where('status', 'Pending');
            })
            ->where('created_at', '<=', now()->subMinutes(1))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('sms_logs')
                  ->whereColumn('sms_logs.order_id', 'orders.id')
                  ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_1->value);
            })
            ->get();

        $this->log('info', '[POD Reminder] Eligible pending COD orders found: ' . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        if ($orders->isEmpty()) {
            $this->log('info', '[POD Reminder] No eligible orders at this time — job exiting.');
            return;
        }

        // ── 3. Boot Twilio ───────────────────────────────────────────────────
        try {
            $twilio = new TwilioService();
            $this->log('info', '[POD Reminder] TwilioService initialised successfully.');
        } catch (\Exception $e) {
            $this->log('error', '[POD Reminder] TwilioService init failed — aborting job.', [
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // ── 4. Send SMS per order ────────────────────────────────────────────
        $sent    = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $customer = $order->customer;

            if (!$customer) {
                $this->log('warning', '[POD Reminder] Order ' . $order->unique_id . ' has no customer — skipping.');
                $skipped++;
                continue;
            }

            if (!$customer->phone) {
                $this->log('warning', '[POD Reminder] Customer #' . $customer->id . ' (order ' . $order->unique_id . ') has no phone number — skipping.', [
                    'customer_id'    => $customer->id,
                    'customer_email' => $customer->email,
                    'order_id'       => $order->unique_id,
                ]);
                $skipped++;
                continue;
            }

            // TEMP TEST ONLY — remove this block before go-live
            if ($customer->unique_id !== 'CUS-LKNW-UNUM') {
                $this->log('info', '[POD Reminder] TEMP SKIP — order ' . $order->unique_id . ' skipped (test mode, customer ' . $customer->unique_id . ' is not CUS-LKNW-UNUM).');
                $skipped++;
                continue;
            }
            // END TEMP TEST ONLY

            $paymentLink = route('front.checkout.order-payment-form', [
                'order' => encrypt($order->unique_id),
            ]);

            $message = str_replace('{{payment_link}}', $paymentLink, $messageTemplate);

            $this->log('info', '[POD Reminder] Attempting SMS for order ' . $order->unique_id, [
                'order_id'       => $order->unique_id,
                'order_number'   => $order->order_number,
                'customer_id'    => $customer->id,
                'customer_name'  => trim($customer->first_name . ' ' . $customer->last_name),
                'customer_email' => $customer->email,
                'phone'          => $customer->phone,
                'balance_due'    => $order->balance_due,
                'payment_link'   => $paymentLink,
                'message'        => $message,
            ]);

            try {
                $result = $twilio->sendSms($customer->phone, $message, [], [
                    'order_id'    => $order->id,
                    'customer_id' => $customer->id,
                    'sms_type'    => SmsType::POD_PAYMENT_REMINDER_1->value,
                ]);

                if ($result['success'] ?? false) {
                    $this->log('info', '[POD Reminder] SMS sent successfully for order ' . $order->unique_id, [
                        'twilio_sid' => $result['sid'] ?? null,
                        'to'         => $result['to'] ?? $customer->phone,
                        'from'       => $result['from'] ?? null,
                    ]);
                    $sent++;
                } else {
                    $this->log('warning', '[POD Reminder] SMS returned non-success for order ' . $order->unique_id, [
                        'response' => $result,
                    ]);
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->log('error', '[POD Reminder] SMS exception for order ' . $order->unique_id, [
                    'error'          => $e->getMessage(),
                    'order_id'       => $order->unique_id,
                    'customer_id'    => $customer->id,
                    'customer_phone' => $customer->phone,
                ]);
                $skipped++;
            }
        }

        // ── 5. Summary ───────────────────────────────────────────────────────
        $this->log('info', '[POD Reminder] Job completed.', [
            'started_at'   => $startedAt->toDateTimeString(),
            'finished_at'  => now()->toDateTimeString(),
            'duration'     => round(now()->diffInMilliseconds($startedAt)) . 'ms',
            'total_orders' => $orders->count(),
            'sms_sent'     => $sent,
            'sms_skipped'  => $skipped,
        ]);
    }
}
