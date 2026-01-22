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

class SendReturnDayBeforeRentalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Return Day-before rental reminder start.');

        $messages = ConfigurationHelper::getSettings('Default Sales Funnel Settings') ?? [];

        // Normalize config
        $storeMessage    = trim($messages['rental_return_day_before_store_message'] ?? '');
        $truckMessage    = trim($messages['rental_return_day_before_truck_message'] ?? '');

        $storeEnabledRaw = $messages['rental_return_day_before_store_message_enabled'] ?? null;
        $truckEnabledRaw = $messages['rental_return_day_before_truck_message_enabled'] ?? null;

        $storeEnabled = filter_var($storeEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        $truckEnabled = filter_var($truckEnabledRaw, FILTER_VALIDATE_BOOLEAN);

        if (!$storeEnabled && !$truckEnabled) {
            \Log::channel('jobs')->info('No day-before rental messages enabled.');
            return;
        }

        if (($storeEnabled || $truckEnabled) && $storeMessage === '' && $truckMessage === '') {
            \Log::channel('jobs')->info('No day-before rental return messages configured.');
            return;
        }

        // Tomorrow's date in America/Chicago timezone
        $today = Carbon::now('America/Chicago')->addDay()->toDateString();

        $twilio = new TwilioService();

        $records = OrderProduct::with([
                'order.shippingAddress',
                'product',
            ])
            ->whereHas('product', function ($query) {
                $query->where('is_default_funnel', true);
            })
            ->where('product_data->product_type', 'Rental')
            ->whereDate('pickup_date', $today)
            ->where('pickup_status', 'Pending')
            ->get();

        $count     = $records->count();
        $sentCount = 0;

        \Log::channel('jobs')->info("Preparing to send {$count} Return day-before rental reminder message(s).");

        foreach ($records as $record) {
            $phoneNumber = data_get($record, 'order.shippingAddress.phone');

            if (!$phoneNumber) {
                \Log::channel('jobs')->warning('No phone number for order product', [
                    'order_product_id' => $record->id,
                ]);
                continue;
            }

            if ($record->pickup_transport_mode === 'Store') {
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

            $response = $twilio->sendSms($phoneNumber, $message, [], [
                'order_id'         => $record->order_id,
                'order_product_id' => $record->id,
                'customer_id'      => optional($record->order)->customer_id,
                'sms_type'         => SmsType::RETURN_DAY_BEFORE,
            ]);

            if (($response['success'] ?? false) === true) {
                $sentCount++;
                \Log::channel('jobs')->info('Sent return day-before rental SMS successfully.', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'sid'              => $response['sid'] ?? null,
                    'message'          => $message,
                ]);

            } else {
                \Log::channel('jobs')->warning('Failed to send rental reminder SMS', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'error'            => $response['error'] ?? null,
                    'message'          => $message,
                ]);
            }
        }

        \Log::channel('jobs')->info("Finished sending {$sentCount} Return day-before rental reminder message(s) out of {$count} records.");
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Return Day-before rental reminder end.');
    }
}
