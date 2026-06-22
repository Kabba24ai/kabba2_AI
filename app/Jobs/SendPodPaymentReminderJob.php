<?php

namespace App\Jobs;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\PodPaymentLinkEvent;
use App\Enums\Orders\PodPaymentLinkStatus;
use App\Helpers\ConfigurationHelper;
use App\Models\Orders\Order;
use App\Models\Orders\PodPaymentLink;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendPodPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private TwilioService $twilio;

    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('jobs')->{$level}($message, $context);
    }

    public function handle(): void
    {
        $startedAt = now();
        $this->log('info', '[POD Reminder] Job started at ' . $startedAt->toDateTimeString());

        $settings = ConfigurationHelper::getSettings('Default Sales Funnel Settings');

        try {
            $this->twilio = new TwilioService();
            $this->log('info', '[POD Reminder] TwilioService initialised successfully.');
        } catch (\Exception $e) {
            $this->log('error', '[POD Reminder] TwilioService init failed — aborting job.', [
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $this->processReminder1($settings);
        $this->processReminder2($settings);
        $this->processReminder3($settings);
        $this->processReminder4($settings);

        $this->log('info', '[POD Reminder] Job completed.', [
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'duration'    => round(now()->diffInMilliseconds($startedAt)) . 'ms',
        ]);
    }

    // ── Reminder #1: 1 hour after POD order is created ───────────────────────
    private function processReminder1(array $settings): void
    {
        $tag = '[POD Reminder #1]';

        if (($settings['pod_payment_reminder_1_enabled'] ?? null) != '1') {
            $this->log('info', "$tag Disabled in settings — skipping.");
            return;
        }

        $messageTemplate = $settings['pod_payment_reminder_1_message'] ?? null;
        if (!$messageTemplate) {
            $this->log('warning', "$tag No message template — skipping.");
            return;
        }

        $orders = Order::with('customer')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            //->where('created_at', '<=', now()->subHour())
            ->where('created_at', '<=', now()-> subMinutes(1))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_1->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        $this->sendBatch($tag, $orders, $messageTemplate, SmsType::POD_PAYMENT_REMINDER_1, PodPaymentLinkEvent::Reminder1Sent);
    }

    // ── Reminder #2: 7:00 AM on the rental start date (delivery_date) ────────
    private function processReminder2(array $settings): void
    {
        $tag = '[POD Reminder #2]';

        if (($settings['pod_payment_reminder_2_enabled'] ?? null) != '1') {
            $this->log('info', "$tag Disabled in settings — skipping.");
            return;
        }

        if (now()->hour < 7) {
            $this->log('info', "$tag Before 7:00 AM — skipping.");
            return;
        }

        $messageTemplate = $settings['pod_payment_reminder_2_message'] ?? null;
        if (!$messageTemplate) {
            $this->log('warning', "$tag No message template — skipping.");
            return;
        }

        $today = now()->toDateString();

        $orders = Order::with('customer')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereHas('products', fn($q) => $q->whereDate('delivery_date', $today))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_2->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        $this->sendBatch($tag, $orders, $messageTemplate, SmsType::POD_PAYMENT_REMINDER_2, PodPaymentLinkEvent::Reminder2Sent);
    }

    // ── Reminder #3: Last Chance — after rental start date has passed ─────────
    private function processReminder3(array $settings): void
    {
        $tag = '[POD Reminder #3]';

        if (($settings['pod_payment_reminder_3_enabled'] ?? null) != '1') {
            $this->log('info', "$tag Disabled in settings — skipping.");
            return;
        }

        $messageTemplate = $settings['pod_payment_reminder_3_message'] ?? null;
        if (!$messageTemplate) {
            $this->log('warning', "$tag No message template — skipping.");
            return;
        }

        $today = now()->toDateString();

        $orders = Order::with('customer')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereHas('products', fn($q) => $q->whereDate('delivery_date', '<', $today))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_3->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        $this->sendBatch($tag, $orders, $messageTemplate, SmsType::POD_PAYMENT_REMINDER_3, PodPaymentLinkEvent::Reminder3Sent);
    }

    // ── Reminder #4: Closeout — 24 hours after Reminder #3 ───────────────────
    private function processReminder4(array $settings): void
    {
        $tag = '[POD Reminder #4]';

        if (($settings['pod_payment_reminder_4_enabled'] ?? null) != '1') {
            $this->log('info', "$tag Disabled in settings — skipping.");
            return;
        }

        $messageTemplate = $settings['pod_payment_reminder_4_message'] ?? null;
        if (!$messageTemplate) {
            $this->log('warning', "$tag No message template — skipping.");
            return;
        }

        $orders = Order::with('customer')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_3->value)
                ->where('sms_logs.created_at', '<=', now()->subHours(24)))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_REMINDER_4->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        $this->sendBatch($tag, $orders, $messageTemplate, SmsType::POD_PAYMENT_REMINDER_4, PodPaymentLinkEvent::Reminder4Sent);
    }

    // ── Shared send loop ──────────────────────────────────────────────────────
    private function sendBatch(
        string              $tag,
        $orders,
        string              $messageTemplate,
        SmsType             $smsType,
        PodPaymentLinkEvent $linkEvent
    ): void {
        if ($orders->isEmpty()) {
            $this->log('info', "$tag No eligible orders — nothing to send.");
            return;
        }

        $sent    = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $customer = $order->customer;

            if (!$customer) {
                $this->log('warning', "$tag Order {$order->unique_id} has no customer — skipping.");
                $skipped++;
                continue;
            }

            if (!$customer->phone) {
                $this->log('warning', "$tag Customer #{$customer->id} (order {$order->unique_id}) has no phone — skipping.", [
                    'customer_id'    => $customer->id,
                    'customer_email' => $customer->email,
                    'order_id'       => $order->unique_id,
                ]);
                $skipped++;
                continue;
            }

            // TEMP TEST ONLY — remove this block before go-live
            if ($customer->unique_id !== 'CUS-LKNW-UNUM') {
                $this->log('info', "$tag TEMP SKIP — order {$order->unique_id} skipped (test mode, customer {$customer->unique_id} is not CUS-LKNW-UNUM).");
                $skipped++;
                continue;
            }

            // TEMP DATE GUARD — only process orders on/after 2026-06-21; remove before go-live
            if ($order->created_at->toDateString() < '2026-06-21') {
                $this->log('info', "$tag TEMP SKIP — order {$order->unique_id} skipped (created_at {$order->created_at->toDateString()} is before cutoff 2026-06-22).");
                $skipped++;
                continue;
            }
            // END TEMP DATE GUARD

            // Ensure a pod_payment_links tracker row exists for this order
            $podLink = $this->findOrCreatePodPaymentLink($order);

            $paymentLink = route('front.checkout.order-payment-form', [
                'order' => encrypt($order->unique_id),
            ]);

            $message = str_replace('{{payment_link}}', $paymentLink, $messageTemplate);

            $this->log('info', "$tag Attempting SMS for order {$order->unique_id}", [
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
                $result = $this->twilio->sendSms($customer->phone, $message, [], [
                    'order_id'    => $order->id,
                    'customer_id' => $customer->id,
                    'sms_type'    => $smsType->value,
                ]);

                if ($result['success'] ?? false) {
                    $this->log('info', "$tag SMS sent for order {$order->unique_id}", [
                        'twilio_sid' => $result['sid'] ?? null,
                        'to'         => $result['to'] ?? $customer->phone,
                        'from'       => $result['from'] ?? null,
                    ]);

                    // ── Record to pod_payment_links + pod_payment_link_activities ──
                    $podLink->recordEvent($linkEvent, [
                        'twilio_sid' => $result['sid'] ?? null,
                        'phone'      => $customer->phone,
                        'sms_type'   => $smsType->value,
                    ]);

                    $sent++;
                } else {
                    $this->log('warning', "$tag SMS returned non-success for order {$order->unique_id}", [
                        'response' => $result,
                    ]);
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->log('error', "$tag SMS exception for order {$order->unique_id}", [
                    'error'          => $e->getMessage(),
                    'order_id'       => $order->unique_id,
                    'customer_id'    => $customer->id,
                    'customer_phone' => $customer->phone,
                ]);
                $skipped++;
            }
        }

        $this->log('info', "$tag Batch done — sent: $sent, skipped: $skipped.");
    }

    // ── Pod payment link tracker ──────────────────────────────────────────────
    /**
     * Get or create the pod_payment_links row for an order.
     * On first creation, also writes a LinkCreated activity row.
     */
    private function findOrCreatePodPaymentLink(Order $order): PodPaymentLink
    {
        $link = PodPaymentLink::firstOrCreate(
            ['order_id' => $order->id],
            [
                'payment_link_token'      => Str::uuid()->toString(),
                'pod_status'              => PodPaymentLinkStatus::Pending->value,
                'payment_link_created_at' => now(),
            ]
        );

        if ($link->wasRecentlyCreated) {
            $link->activities()->create([
                'order_id' => $order->id,
                'event'    => PodPaymentLinkEvent::LinkCreated->value,
                'metadata' => null,
            ]);

            $this->log('info', '[POD Link] Created pod_payment_links record for order ' . $order->unique_id, [
                'pod_payment_link_id' => $link->id,
                'token'               => $link->payment_link_token,
            ]);
        }

        return $link;
    }
}
