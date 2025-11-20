<?php

namespace App\Jobs;

use App\Helpers\ConfigurationHelper;
use App\Models\Orders\OrderProduct;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDayBeforeRentalReminderJob implements ShouldQueue
{
    use Queueable;

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
        // Send the day-before rental reminder
        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Day-before rental reminder start.');

        $messages = ConfigurationHelper::getSettings('Default Sales Funnel Settings');

        if (empty($messages['rental_day_before_store_message']) && empty($messages['rental_day_before_truck_message'])) {
            \Log::channel('jobs')->info('No day-before rental messages configured.');
            return;
        }


        $today = Carbon::now('America/Chicago')->addDay()->toDateString(); // Tomorrow's date in America/Chicago timezone

        // $now = now()->format('Y-m-d H:i:s');
        // $carbonDate= Carbon::now('America/Chicago')->format('Y-m-d H:i:s');
        // \Log::channel('jobs')->info("Day-before rental reminders for date: {$carbonDate}, now: {$now}");

        $twilio = new TwilioService();

        $records = OrderProduct::with([
            'order.shippingAddress',
            'product' => function ($query) {
                $query->where('is_default_funnel', true);
            }
        ])->where('product_data->product_type', 'Rental')->whereDate('pickup_date', $today)->where('pickup_status', 'Pending')->get();

        $count = $records->count();
        $sentCount = 0;
        \Log::channel('jobs')->info("Preparing to send {$count} day-before rental reminder message(s).");

        foreach ($records as $record) {
            $phoneNumber = $record->order->shippingAddress->phone ?? null;
            if ($record->pickup_transport_mode === 'Store') {
                if ($messages['rental_day_before_store_message_enabled'] == false) {
                    continue; // Skip if store message is not enabled
                }
                $message = $messages['rental_day_before_store_message'];
            } else {
                if ($messages['rental_day_before_truck_message_enabled'] == false) {
                    continue; // Skip if truck message is not enabled
                }
                $message = $messages['rental_day_before_truck_message'];
            }
            if ($phoneNumber) {
                $response = $twilio->sendSms($phoneNumber, $message);
                if($response['success']){
                    $sentCount++;
                }
            }
        }

        \Log::channel('jobs')->info("Finished sending {$sentCount} day-before rental reminder message(s) out of {$count} records.");

        \Log::channel('jobs')->info(now()->format('Y-m-d H:i:s') . ' Day-before rental reminder end.');
    }
}
