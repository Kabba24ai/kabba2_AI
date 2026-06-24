<?php

namespace App\Jobs;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\PodPaymentLinkEvent;
use App\Enums\Orders\PodPaymentLinkStatus;
use App\Helpers\ConfigurationHelper;
use App\Models\Orders\Order;
use App\Models\Orders\PodPaymentLink;
use App\Services\PaymentShortLinkService;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendPodPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private TwilioService $twilio;
    private PaymentShortLinkService $shortLinks;

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
            $this->twilio     = new TwilioService();
            $this->shortLinks = new PaymentShortLinkService();
            $this->log('info', '[POD Reminder] TwilioService initialised successfully.');
        } catch (\Exception $e) {
            $this->log('error', '[POD Reminder] TwilioService init failed — aborting job.', [
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $this->processPaymentLinkMessage($settings);
        $this->processDayBefore3pm($settings);
        $this->processFinalReminder9am($settings);
        $this->processLastDitch4pm($settings);

        $this->log('info', '[POD Reminder] Job completed.', [
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'duration'    => round(now()->diffInMilliseconds($startedAt)) . 'ms',
        ]);
    }

    // ── Payment Link Message: 1 minute after COD order confirmation ──────────
    private function processPaymentLinkMessage(array $settings): void
    {
        $tag = '[POD Payment Link]';

        $truckEnabled = ($settings['pod_payment_link_truck_message_enabled'] ?? null) == '1';
        $storeEnabled = ($settings['pod_payment_link_store_message_enabled'] ?? null) == '1';

        if (!$truckEnabled && !$storeEnabled) {
            $this->log('info', "$tag Both truck and store messages disabled — skipping.");
            return;
        }

        $truckTemplate = $settings['pod_payment_link_truck_message'] ?? null;
        $storeTemplate = $settings['pod_payment_link_store_message'] ?? null;

        $orders = Order::with('customer', 'products.deliveryStore')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::COD_ORDER_NOTIFICATION->value)
                ->where('sms_logs.created_at', '<=', now()->subMinutes(1)))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_PAYMENT_LINK->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        if ($orders->isEmpty()) {
            $this->log('info', "$tag No eligible orders — nothing to send.");
            return;
        }

        $sent        = 0;
        $skipped     = 0;
        $tempSkipped = [];

        foreach ($orders as $order) {
            $customer = $order->customer;

            if (!$customer) {
                $this->log('warning', "$tag Order {$order->unique_id} has no customer — skipping.");
                $skipped++;
                continue;
            }

            if (!$customer->phone) {
                $this->log('warning', "$tag Customer #{$customer->id} (order {$order->unique_id}) has no phone — skipping.");
                $skipped++;
                continue;
            }

            // TEMP TEST ONLY — only send to this specific customer; remove before go-live
            if ($customer->unique_id !== 'CUS-LKNW-UNUM') {
                $this->log('info', "$tag TEMP SKIP — order {$order->unique_id} skipped (test mode, customer {$customer->unique_id} is not CUS-LKNW-UNUM).");
                $skipped++;
                continue;
            }

            // TEMP DATE GUARD — only process orders on/after 2026-06-21; intentional, do not remove
            if ($order->created_at->toDateString() < '2026-06-21') {
                $tempSkipped[] = $order->unique_id;
                $skipped++;
                continue;
            }
            // END TEMP DATE GUARD

            $firstProduct = $order->products->first();
            $isTruck      = $firstProduct && strtolower($firstProduct->delivery_transport_mode ?? '') === 'truck';

            if ($isTruck) {
                if (!$truckEnabled || !$truckTemplate) {
                    $this->log('info', "$tag Order {$order->unique_id} is truck but truck message disabled/empty — skipping.");
                    $skipped++;
                    continue;
                }
                $messageTemplate = $truckTemplate;
                $storeName       = '';
            } else {
                if (!$storeEnabled || !$storeTemplate) {
                    $this->log('info', "$tag Order {$order->unique_id} is store but store message disabled/empty — skipping.");
                    $skipped++;
                    continue;
                }
                $messageTemplate = $storeTemplate;
                $storeName       = $firstProduct?->deliveryStore?->store_name ?? '';
            }

            $podLink = $this->findOrCreatePodPaymentLink($order);

            $longUrl = route('front.checkout.order-payment-form', [
                'order' => encrypt($order->unique_id),
            ]);

            $shortLink   = $this->shortLinks->shorten($longUrl, $order->id, $order->customer_id);
            $paymentLink = $this->shortLinks->shortUrlFor($shortLink->token);

            $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $message = str_replace(
                ['{{customer_name}}', '{{store_name}}', '{{payment_link}}'],
                [$customerName, $storeName, $paymentLink],
                $messageTemplate
            );

            $this->log('info', "$tag Attempting SMS for order {$order->unique_id}", [
                'order_id'     => $order->unique_id,
                'customer_id'  => $customer->id,
                'is_truck'     => $isTruck,
                'store_name'   => $storeName,
                'payment_link' => $paymentLink,
                'message'      => $message,
            ]);

            try {
                $result = $this->twilio->sendSms($customer->phone, $message, [], [
                    'order_id'            => $order->id,
                    'customer_id'         => $customer->id,
                    'sms_type'            => SmsType::POD_PAYMENT_LINK->value,
                    'pod_payment_link_id' => $podLink->id,
                ]);

                if ($result['success'] ?? false) {
                    $this->log('info', "$tag SMS sent for order {$order->unique_id}", [
                        'twilio_sid' => $result['sid'] ?? null,
                    ]);

                    $podLink->recordEvent(PodPaymentLinkEvent::PaymentLinkSent, [
                        'twilio_sid' => $result['sid'] ?? null,
                        'phone'      => $customer->phone,
                        'sms_type'   => SmsType::POD_PAYMENT_LINK->value,
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
                    'error'       => $e->getMessage(),
                    'order_id'    => $order->unique_id,
                    'customer_id' => $customer->id,
                ]);
                $skipped++;
            }
        }

        if (!empty($tempSkipped)) {
            $this->log('info', "$tag TEMP SKIP — " . count($tempSkipped) . " order(s) before cutoff 2026-06-21.", [
                'order_ids' => $tempSkipped,
            ]);
        }

        $this->log('info', "$tag Batch done — sent: $sent, skipped: $skipped.");
    }

    // ── Day Before Delivery Reminder: 3:00 PM day before (if still unpaid) ──────
    private function processDayBefore3pm(array $settings): void
    {
        $tag = '[POD Day Before 3PM]';

        if (now()->timezone('America/Chicago')->hour < 15) {
            $this->log('info', "$tag Before 3:00 PM — skipping.");
            return;
        }

        $truckEnabled  = ($settings['pod_day_before_truck_message_enabled'] ?? null) == '1';
        $storeEnabled  = ($settings['pod_day_before_store_message_enabled'] ?? null) == '1';
        $truckTemplate = $settings['pod_day_before_truck_message'] ?? null;
        $storeTemplate = $settings['pod_day_before_store_message'] ?? null;

        if (!$truckEnabled && !$storeEnabled) {
            $this->log('info', "$tag Both messages disabled — skipping.");
            return;
        }

        $tomorrow = now()->timezone('America/Chicago')->addDay()->toDateString();

        $orders = Order::with('customer', 'products.deliveryStore')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereHas('products', fn($q) => $q->whereDate('delivery_date', $tomorrow))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_DAY_BEFORE->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        if ($orders->isEmpty()) {
            $this->log('info', "$tag No eligible orders — nothing to send.");
            return;
        }

        $this->sendTruckStoreBatch($tag, $orders, $truckTemplate, $storeTemplate, $truckEnabled, $storeEnabled, SmsType::POD_DAY_BEFORE, PodPaymentLinkEvent::DayBeforeSent);
    }

    // ── Final Rental Reminder: 9:00 AM on delivery day (if still unpaid) ────────
    private function processFinalReminder9am(array $settings): void
    {
        $tag = '[POD Final Reminder 9AM]';

        if (now()->timezone('America/Chicago')->hour < 9) {
            $this->log('info', "$tag Before 9:00 AM — skipping.");
            return;
        }

        $truckEnabled  = ($settings['pod_final_reminder_truck_message_enabled'] ?? null) == '1';
        $storeEnabled  = ($settings['pod_final_reminder_store_message_enabled'] ?? null) == '1';
        $truckTemplate = $settings['pod_final_reminder_truck_message'] ?? null;
        $storeTemplate = $settings['pod_final_reminder_store_message'] ?? null;

        if (!$truckEnabled && !$storeEnabled) {
            $this->log('info', "$tag Both messages disabled — skipping.");
            return;
        }

        $today = now()->timezone('America/Chicago')->toDateString();

        $orders = Order::with('customer', 'products.deliveryStore')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereHas('products', fn($q) => $q->whereDate('delivery_date', $today))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_FINAL_REMINDER->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        if ($orders->isEmpty()) {
            $this->log('info', "$tag No eligible orders — nothing to send.");
            return;
        }

        $this->sendTruckStoreBatch($tag, $orders, $truckTemplate, $storeTemplate, $truckEnabled, $storeEnabled, SmsType::POD_FINAL_REMINDER, PodPaymentLinkEvent::FinalReminderSent);
    }

    // ── Last Ditch Recovery: 4:00 PM on delivery day (if still unpaid) ────────
    private function processLastDitch4pm(array $settings): void
    {
        $tag = '[POD Last Ditch 4PM]';

        if (now()->timezone('America/Chicago')->hour < 16) {
            $this->log('info', "$tag Before 4:00 PM — skipping.");
            return;
        }

        $truckEnabled  = ($settings['pod_last_ditch_truck_message_enabled'] ?? null) == '1';
        $storeEnabled  = ($settings['pod_last_ditch_store_message_enabled'] ?? null) == '1';
        $truckTemplate = $settings['pod_last_ditch_truck_message'] ?? null;
        $storeTemplate = $settings['pod_last_ditch_store_message'] ?? null;

        if (!$truckEnabled && !$storeEnabled) {
            $this->log('info', "$tag Both messages disabled — skipping.");
            return;
        }

        $today = now()->timezone('America/Chicago')->toDateString();

        $orders = Order::with('customer', 'products.deliveryStore')
            ->whereHas('payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->whereHas('products', fn($q) => $q->whereDate('delivery_date', $today))
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'orders.id')
                ->where('sms_logs.sms_type', SmsType::POD_LAST_DITCH->value))
            ->get();

        $this->log('info', "$tag Eligible orders: " . $orders->count(), [
            'order_ids' => $orders->pluck('unique_id')->toArray(),
        ]);

        if ($orders->isEmpty()) {
            $this->log('info', "$tag No eligible orders — nothing to send.");
            return;
        }

        $this->sendTruckStoreBatch($tag, $orders, $truckTemplate, $storeTemplate, $truckEnabled, $storeEnabled, SmsType::POD_LAST_DITCH, PodPaymentLinkEvent::LastDitchSent);
    }

    // ── Shared truck/store send loop ──────────────────────────────────────────
    private function sendTruckStoreBatch(
        string $tag,
        $orders,
        ?string $truckTemplate,
        ?string $storeTemplate,
        bool $truckEnabled,
        bool $storeEnabled,
        SmsType $smsType,
        PodPaymentLinkEvent $linkEvent
    ): void {
        $sent        = 0;
        $skipped     = 0;
        $tempSkipped = [];

        foreach ($orders as $order) {
            $customer = $order->customer;

            if (!$customer) {
                $this->log('warning', "$tag Order {$order->unique_id} has no customer — skipping.");
                $skipped++;
                continue;
            }

            if (!$customer->phone) {
                $this->log('warning', "$tag Customer #{$customer->id} (order {$order->unique_id}) has no phone — skipping.");
                $skipped++;
                continue;
            }

            // TEMP TEST ONLY — only send to this specific customer; remove before go-live
            if ($customer->unique_id !== 'CUS-LKNW-UNUM') {
                $this->log('info', "$tag TEMP SKIP — order {$order->unique_id} skipped (test mode, customer {$customer->unique_id} is not CUS-LKNW-UNUM).");
                $skipped++;
                continue;
            }

            // TEMP DATE GUARD — only process orders on/after 2026-06-21; intentional, do not remove
            if ($order->created_at->toDateString() < '2026-06-21') {
                $tempSkipped[] = $order->unique_id;
                $skipped++;
                continue;
            }
            // END TEMP DATE GUARD

            $firstProduct = $order->products->first();
            $isTruck      = $firstProduct && strtolower($firstProduct->delivery_transport_mode ?? '') === 'truck';

            if ($isTruck) {
                if (!$truckEnabled || !$truckTemplate) {
                    $this->log('info', "$tag Order {$order->unique_id} is truck but truck message disabled/empty — skipping.");
                    $skipped++;
                    continue;
                }
                $messageTemplate = $truckTemplate;
                $storeName       = '';
            } else {
                if (!$storeEnabled || !$storeTemplate) {
                    $this->log('info', "$tag Order {$order->unique_id} is store but store message disabled/empty — skipping.");
                    $skipped++;
                    continue;
                }
                $messageTemplate = $storeTemplate;
                $storeName       = $firstProduct?->deliveryStore?->store_name ?? '';
            }

            $podLink = $this->findOrCreatePodPaymentLink($order);

            $longUrl = route('front.checkout.order-payment-form', [
                'order' => encrypt($order->unique_id),
            ]);

            $shortLink   = $this->shortLinks->shorten($longUrl, $order->id, $order->customer_id);
            $paymentLink = $this->shortLinks->shortUrlFor($shortLink->token);

            $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $deliveryDate = $firstProduct?->delivery_date ? Carbon::parse($firstProduct->delivery_date)->format('M d, Y') : '';
            $message = str_replace(
                ['{{customer_name}}', '{{store_name}}', '{{delivery_date}}', '{{payment_link}}'],
                [$customerName, $storeName, $deliveryDate, $paymentLink],
                $messageTemplate
            );

            $this->log('info', "$tag Attempting SMS for order {$order->unique_id}", [
                'order_id'     => $order->unique_id,
                'customer_id'  => $customer->id,
                'is_truck'     => $isTruck,
                'payment_link' => $paymentLink,
                'message'      => $message,
            ]);

            try {
                $result = $this->twilio->sendSms($customer->phone, $message, [], [
                    'order_id'            => $order->id,
                    'customer_id'         => $customer->id,
                    'sms_type'            => $smsType->value,
                    'pod_payment_link_id' => $podLink->id,
                ]);

                if ($result['success'] ?? false) {
                    $this->log('info', "$tag SMS sent for order {$order->unique_id}", [
                        'twilio_sid' => $result['sid'] ?? null,
                    ]);

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
                    'error'       => $e->getMessage(),
                    'order_id'    => $order->unique_id,
                    'customer_id' => $customer->id,
                ]);
                $skipped++;
            }
        }

        if (!empty($tempSkipped)) {
            $this->log('info', "$tag TEMP SKIP — " . count($tempSkipped) . " order(s) before cutoff 2026-06-21.", [
                'order_ids' => $tempSkipped,
            ]);
        }

        $this->log('info', "$tag Batch done — sent: $sent, skipped: $skipped.");
    }

    // ── Shared single-template send loop ─────────────────────────────────────
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

        $sent        = 0;
        $skipped     = 0;
        $tempSkipped = [];

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

            // TEMP TEST ONLY — only send to this specific customer; remove before go-live
            if ($customer->unique_id !== 'CUS-LKNW-UNUM') {
                $this->log('info', "$tag TEMP SKIP — order {$order->unique_id} skipped (test mode, customer {$customer->unique_id} is not CUS-LKNW-UNUM).");
                $skipped++;
                continue;
            }

            // TEMP DATE GUARD — only process orders on/after 2026-06-21; remove before go-live
            if ($order->created_at->toDateString() < '2026-06-21') {
                $tempSkipped[] = $order->unique_id;
                $skipped++;
                continue;
            }
            // END TEMP DATE GUARD

            // Ensure a pod_payment_links tracker row exists for this order
            $podLink = $this->findOrCreatePodPaymentLink($order);

            $longUrl = route('front.checkout.order-payment-form', [
                'order' => encrypt($order->unique_id),
            ]);

            $shortLink   = $this->shortLinks->shorten($longUrl, $order->id, $order->customer_id);
            $paymentLink = $this->shortLinks->shortUrlFor($shortLink->token);

            $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $message = str_replace(
                ['{{customer_name}}', '{{payment_link}}'],
                [$customerName, $paymentLink],
                $messageTemplate
            );

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
                    'order_id'            => $order->id,
                    'customer_id'         => $customer->id,
                    'sms_type'            => $smsType->value,
                    'pod_payment_link_id' => $podLink->id,
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

        if (!empty($tempSkipped)) {
            $this->log('info', "$tag TEMP SKIP — " . count($tempSkipped) . " order(s) before cutoff 2026-06-22.", [
                'order_ids' => $tempSkipped,
            ]);
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
