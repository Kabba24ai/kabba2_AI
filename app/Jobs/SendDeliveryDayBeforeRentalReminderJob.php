<?php

namespace App\Jobs;

use App\Enums\Communication\SmsType;
use App\Helpers\ConfigurationHelper;
use App\Models\Orders\OrderProduct;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendDeliveryDayBeforeRentalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Delivery Day-before rental reminder start.');

        $messages = ConfigurationHelper::getSettings('Default Sales Funnel Settings') ?? [];

        // Normalize config
        $storeMessage    = trim($messages['rental_delivery_day_before_store_message'] ?? '');
        $truckMessage    = trim($messages['rental_delivery_day_before_truck_message'] ?? '');

        $storeEnabledRaw = $messages['rental_delivery_day_before_store_message_enabled'] ?? null;
        $truckEnabledRaw = $messages['rental_delivery_day_before_truck_message_enabled'] ?? null;
        $storeEnabled = filter_var($storeEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        $truckEnabled = filter_var($truckEnabledRaw, FILTER_VALIDATE_BOOLEAN);

        if (!$storeEnabled && !$truckEnabled) {
            \Log::channel('jobs')->info('No day-before rental messages enabled.');
            return;
        }

        // If at least one is enabled, but both messages are empty, bail
        if (($storeEnabled || $truckEnabled) && $storeMessage === '' && $truckMessage === '') {
            \Log::channel('jobs')->info('No day-before rental delivery messages configured.');
            return;
        }

        // Tomorrow's date in America/Chicago timezone
        $today = Carbon::now('America/Chicago')->addDay()->toDateString();

        $twilio = new TwilioService();

        $records = OrderProduct::with([
                'order.shippingAddress',
                'order.customer',
                'deliveryStore',
                'product',
            ])
            ->whereHas('product', function ($query) {
                $query->where('is_default_funnel', true);
            })
            ->where('product_data->product_type', 'Rental')
            ->whereDate('delivery_date', $today)
            ->where('delivery_status', 'Pending')
            // Exclude COD-Pending (POD unpaid) orders — they get a separate POD day-before message
            ->whereDoesntHave('order.payments', fn($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            // Prevent duplicate sends if job somehow fires more than once
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('sms_logs')
                ->whereColumn('sms_logs.order_id', 'order_products.order_id')
                ->where('sms_logs.sms_type', SmsType::DELIVERY_DAY_BEFORE->value))
            ->get();

        $count     = $records->count();
        $sentCount = 0;

        \Log::channel('jobs')->info("Preparing to send {$count} Delivery day-before rental reminder message(s).");

        foreach ($records as $record) {
            $phoneNumber = data_get($record, 'order.shippingAddress.phone');

            if (!$phoneNumber) {
                \Log::channel('jobs')->warning('No phone number for order product', [
                    'order_product_id' => $record->id,
                ]);
                continue;
            }

            if ($record->delivery_transport_mode === 'Store') {
                if (!$storeEnabled || $storeMessage === '') {
                    continue;
                }
                $message = $storeMessage;
            } else {
                if (!$truckEnabled || $truckMessage === '') {
                    continue;
                }
                $message = $truckMessage;
            }

            $customerName = trim(data_get($record, 'order.customer.first_name', '') . ' ' . data_get($record, 'order.customer.last_name', ''));
            $storeName    = $record->deliveryStore?->store_name ?? '';
            $deliveryDate = $record->delivery_date ? Carbon::parse($record->delivery_date)->format('M d, Y') : '';
            // Actual scheduled delivery time for this order product — not a
            // hardcoded standard time, so a Weekend Special (or any other
            // non-standard) delivery schedule renders correctly. Empty
            // string when unset, same fallback convention as $deliveryDate.
            $deliveryTime = $record->delivery_time ? Carbon::parse($record->delivery_time)->format('g:i A') : '';
            $message = str_replace(
                ['{{customer_name}}', '{{store_name}}', '{{delivery_date}}', '{{delivery_time}}'],
                [$customerName, $storeName, $deliveryDate, $deliveryTime],
                $message
            );

            $response = $twilio->sendSms($phoneNumber, $message, [], [
                'order_id'         => $record->order_id,
                'order_product_id' => $record->id,
                'customer_id'      => optional($record->order)->customer_id,
                'sms_type'         => SmsType::DELIVERY_DAY_BEFORE,
            ]);

            if (($response['success'] ?? false) === true) {
                $sentCount++;
                \Log::channel('jobs')->info('Sent delivery day-before rental SMS successfully.', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'sid'              => $response['sid'] ?? null,
                    'message'          => $message,
                ]);
            } else {
                \Log::channel('jobs')->warning('Failed to send rental delivery reminder SMS', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'error'            => $response['error'] ?? null,
                    'message'          => $message,
                ]);
            }
        }

        \Log::channel('jobs')->info("Finished sending {$sentCount} Delivery day-before rental reminder message(s) out of {$count} records.");
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Delivery Day-before rental reminder end.');
    }
}
