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

class SendReturnSameDayRentalReminderJob implements ShouldQueue
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
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Return same-day rental reminder start.');

        $messages = ConfigurationHelper::getSettings('Default Sales Funnel Settings') ?? [];

        // Normalize config for RETURN same-day messages
        $storeMessage    = trim($messages['rental_return_same_day_store_message'] ?? '');
        $truckMessage    = trim($messages['rental_return_same_day_truck_message'] ?? '');

        $storeEnabledRaw = $messages['rental_return_same_day_store_message_enabled'] ?? null;
        $truckEnabledRaw = $messages['rental_return_same_day_truck_message_enabled'] ?? null;

        $storeEnabled = filter_var($storeEnabledRaw, FILTER_VALIDATE_BOOLEAN);
        $truckEnabled = filter_var($truckEnabledRaw, FILTER_VALIDATE_BOOLEAN);

        // If both disabled, no need to proceed
        if (!$storeEnabled && !$truckEnabled) {
            \Log::channel('jobs')->info('No same-day rental return messages enabled.');
            return;
        }

        // If enabled but messages are empty, also bail
        if (($storeEnabled || $truckEnabled) && $storeMessage === '' && $truckMessage === '') {
            \Log::channel('jobs')->info('No same-day rental return messages configured.');
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
            ->whereDate('pickup_date', $today)
            ->where('pickup_status', 'Pending')
            ->get();

        $count     = $records->count();
        $sentCount = 0;

        \Log::channel('jobs')->info("Preparing to send {$count} same-day rental return reminder message(s).");

        foreach ($records as $record) {
            // Safely get phone number
            $phoneNumber = data_get($record, 'order.shippingAddress.phone');

            if (!$phoneNumber) {
                \Log::channel('jobs')->warning('No phone number for order product in same-day rental return reminder.', [
                    'order_product_id' => $record->id,
                ]);
                continue;
            }

            // Decide which template to use
            if ($record->pickup_transport_mode === 'Store') {
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

            $response = $twilio->sendSms($phoneNumber, $message, [], [
                'order_id'         => $record->order_id,
                'order_product_id' => $record->id,
                'customer_id'      => optional($record->order)->customer_id,
                'sms_type'         => SmsType::RETURN_SAME_DAY,
            ]);

            if (($response['success'] ?? false) === true) {
                $sentCount++;
                \Log::channel('jobs')->info('Sent same-day rental return SMS successfully.', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'sid'              => $response['sid'] ?? null,
                    'message'          => $message,
                ]);

            } else {
                \Log::channel('jobs')->warning('Failed to send same-day rental return SMS.', [
                    'order_id'         => $record->order_id,
                    'order_product_id' => $record->id,
                    'phone'            => $phoneNumber,
                    'error'            => $response['error'] ?? null,
                    'message'          => $message,
                ]);
            }
        }

        \Log::channel('jobs')->info("Finished sending {$sentCount} same-day rental return reminder message(s) out of {$count} records.");
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Return same-day rental reminder end.');
    }
}
