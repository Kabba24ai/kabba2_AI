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

        $twilio = new TwilioService();

        $records = OrderProduct::with('order.shippingAddress')->whereDate('pickup_date', $today)->where('pickup_status', 'Pending')->get();

        $count = $records->count();
        $sentCount = 0;
        \Log::channel('jobs')->info("Preparing to send {$count} day-before rental reminder message(s).");

        foreach ($records as $record) {
            $phoneNumber = $record->order->shippingAddress->phone ?? null;
            if ($record->pickup_transport_mode === 'Store') {
                $message = $messages['rental_day_before_store_message'];
            } else {
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
