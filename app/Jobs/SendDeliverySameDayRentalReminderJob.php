<?php

namespace App\Jobs;

use App\Helpers\ConfigurationHelper;
use App\Models\Orders\OrderProduct;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;

class SendDeliverySameDayRentalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send the same-day rental reminder
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Delivery same-day rental reminder start.');

        $messages = ConfigurationHelper::getSettings('Default Sales Funnel Settings') ?? [];

        // Normalize config for DELIVERY same-day messages
        $storeMessage    = trim($messages['rental_delivery_same_day_store_message'] ?? '');
        $truckMessage    = trim($messages['rental_delivery_same_day_truck_message'] ?? '');

        $storeEnabledRaw = $messages['rental_delivery_same_day_store_message_enabled'] ?? null;
        $truckEnabledRaw = $messages['rental_delivery_same_day_truck_message_enabled'] ?? null;
        $storeEnabled = filter_var($storeEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        $truckEnabled = filter_var($truckEnabledRaw, FILTER_VALIDATE_BOOLEAN);

        // If both disabled, no need to proceed
        if (!$storeEnabled && !$truckEnabled) {
            \Log::channel('jobs')->info('No same-day rental delivery messages enabled.');
            return;
        }

        // If at least one is enabled, but both messages are empty, bail
        if (($storeEnabled || $truckEnabled) && $storeMessage === '' && $truckMessage === '') {
            \Log::channel('jobs')->info('No same-day rental delivery messages configured.');
            return;
        }

        // Today's date in America/Chicago timezone
        $today = Carbon::now('America/Chicago')->toDateString();

        $twilio = new TwilioService();

        $records = OrderProduct::with([
                'order.shippingAddress',
                'product',
            ])
            ->whereHas('product', function ($query) {
                $query->where('is_default_funnel', true);
            })
            ->where('product_data->product_type', 'Rental')
            ->whereDate('delivery_date', $today)
            ->where('delivery_status', 'Pending')
            ->get();

        $count     = $records->count();
        $sentCount = 0;

        \Log::channel('jobs')->info("Preparing to send {$count} same-day rental delivery reminder message(s).");

        foreach ($records as $record) {
            // Safely get phone number
            $phoneNumber = data_get($record, 'order.shippingAddress.phone');

            if (!$phoneNumber) {
                \Log::channel('jobs')->warning('No phone number for order product in same-day rental delivery reminder.', [
                    'order_product_id' => $record->id,
                ]);
                continue;
            }

            // Decide which template to use
            if ($record->delivery_transport_mode === 'Store') {
                if (!$storeEnabled || $storeMessage === '') {
                    continue; // Skip if store message is not enabled or empty
                }
                $message = $storeMessage;
            } else {
                if (!$truckEnabled || $truckMessage === '') {
                    continue; // Skip if truck message is not enabled or empty
                }
                $message = $truckMessage;
            }

            $response = $twilio->sendSms($phoneNumber, $message);

            if (($response['success'] ?? false) === true) {
                $sentCount++;
            } else {
                \Log::channel('jobs')->warning('Failed to send same-day rental delivery SMS.', [
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'error'            => $response['error'] ?? null,
                ]);
            }
        }


        $storeCODMessage    = trim($messages['store_delivery_same_day_cod_order_message'] ?? '');
        $truckCODMessage    = trim($messages['truck_delivery_same_day_cod_order_message'] ?? '');

        $storeCODEnabledRaw = $messages['store_delivery_same_day_cod_message_enabled'] ?? null;
        $truckCODEnabledRaw = $messages['truck_delivery_same_day_cod_message_enabled'] ?? null;
        $storeCODEnabled = filter_var($storeCODEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        $truckCODEnabled = filter_var($truckCODEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        // If both disabled, no need to proceed
        if (!$storeCODEnabled && !$truckCODEnabled) {
            \Log::channel('jobs')->info('No same-day rental delivery COD messages enabled.');
            return;
        }

        if (($storeCODEnabled || $truckCODEnabled) && $storeCODMessage === '' && $truckCODMessage === '') {
            \Log::channel('jobs')->info('No same-day rental delivery COD messages configured.');
            return;
        }

        $sentSameDayCODCount = 0;

        foreach ($records as $record) {
            // Safely get phone number
            $phoneNumber = data_get($record, 'order.shippingAddress.phone');

            if (!$phoneNumber) {
                \Log::channel('jobs')->warning('No phone number for order product in same-day rental delivery reminder.', [
                    'order_product_id' => $record->id,
                ]);
                continue;
            }

            // Decide which template to use
            if ($record->delivery_transport_mode === 'Store') {
                if (!$storeCODEnabled || $storeCODMessage === '') {
                    continue; // Skip if store message is not enabled or empty
                }
                $message = $storeCODMessage;
            } else {
                if (!$truckCODEnabled || $truckCODMessage === '') {
                    continue; // Skip if truck message is not enabled or empty
                }
                $message = $truckCODMessage;
            }

            $response = $twilio->sendSms($phoneNumber, $message);

            if (($response['success'] ?? false) === true) {
                $sentSameDayCODCount++;
            } else {
                \Log::channel('jobs')->warning('Failed to send same-day rental delivery SMS.', [
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'error'            => $response['error'] ?? null,
                ]);
            }
        }

        \Log::channel('jobs')->info("Finished sending {$sentCount} same-day rental delivery reminder message(s) out of {$count} records.");
        \Log::channel('jobs')->info("Finished sending {$sentSameDayCODCount} same-day rental delivery COD message(s) out of {$count} records.");
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Delivery same-day rental reminder end.');
    }
}
