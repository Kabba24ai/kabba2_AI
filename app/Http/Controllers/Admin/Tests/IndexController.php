<?php

namespace App\Http\Controllers\Admin\Tests;

use App\Enums\Orders\OrderPaymentMethod;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\TwilioService;

class IndexController extends Controller
{
    // new method here
    public  function sendFirebaseNotification()
    {
        $firebase = new \App\Services\FirebaseService();
        $title = 'Test Notification';
        $body = 'This is a test notification sent from IndexController.';
        $result = $firebase->sendToAllDevices($title, $body, [
            'order_id' => 'test_order_id',
            'order_unique_id' => 'test_order_unique_id',
            'order_number' => 'test_order_number',
        ]);
        return response()->json(['status' => 'Notification sent.', 'result' => $result]);
    }

    public function listTimezonesAndCurrentTimes()
    {
        $serverTimezone = date_default_timezone_get();
        $serverTime = date('Y-m-d h:i:s A');

        $appTimezone = config('app.timezone') ?: 'UTC';
        $appTime = (new \DateTime('now', new \DateTimeZone($appTimezone)))->format('Y-m-d h:i:s A');

        $tennesseeTimezone = 'America/Chicago';
        $tennesseeTime = (new \DateTime('now', new \DateTimeZone($tennesseeTimezone)))->format('Y-m-d h:i:s A');

        $indiaTimezone = 'Asia/Kolkata';
        $indiaTime = (new \DateTime('now', new \DateTimeZone($indiaTimezone)))->format('Y-m-d h:i:s A');

        return response()->json([
            'server_timezone' => $serverTimezone,
            'server_time' => $serverTime,
            'app_timezone' => $appTimezone,
            'app_time' => $appTime,
            'tennessee_timezone' => $tennesseeTimezone,
            'tennessee_time' => $tennesseeTime,
            'india_timezone' => $indiaTimezone,
            'india_time' => $indiaTime,
        ]);
    }

    public function testSendCodSms(){
        $order = \App\Models\Orders\Order::query()->with('lastPayment')->first();

        $smsSetting = ConfigurationHelper::getSettings('Default Sales Funnel Settings');

        if (!$smsSetting || !$smsSetting['cod_message_enabled'] || empty($order->billingAddress->phone)) {
            return response()->json(['status' => 'SMS settings not enabled or phone number missing.']);
        }

        if ($order->lastPayment->payment_method == OrderPaymentMethod::COD) {
            event(new \App\Events\Front\Checkout\OrderPlacedEvent(
                order: $order,
                customer: $order->customer,
                payment: $order->lastPayment,
                orderActionType: null,
                employee: null
            ));
        }
        return response()->json(['status' => 'COD SMS event triggered.']);
    }


    public function updateUsersEmail()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->email = str_replace('@kabba.com', '@kabba.ai', $user->email);
            $user->save();
        }
        return response()->json(['status' => 'User emails updated successfully.']);
    }

    public function sendDayBeforeJob()
    {
        // Dispatch the job to send day-before rental reminders
        \App\Jobs\SendDayBeforeRentalReminderJob::dispatch();
        return response()->json(['status' => 'Day-before rental reminder job dispatched.']);
    }

    public function sendSameDayJob()
    {
        // Dispatch the job to send same-day rental reminders
        \App\Jobs\SendSameDayRentalReminderJob::dispatch();
        return response()->json(['status' => 'Same-day rental reminder job dispatched.']);
    }

    public function sendSms()
    {
        $twilio = new TwilioService();
        $to = '+918000912126'; // Replace with the destination phone number
        $message = 'Hello, this is a test message from Twilio!';
        $options = [
            'media_urls' => ['https://images.unsplash.com/photo-1545093149-618ce3bcf49d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=668&q=80']
        ];
        $result = $twilio->sendSms($to, $message, $options);
        return response()->json($result);
    }

    public function sendWhatsApp(){
        $twilio = new TwilioService();
        $to = '+918000912126'; // Replace with the destination WhatsApp number
        $message = 'Hello, this is a test WhatsApp message from Twilio!';
        $options = [
            'media_urls' => ['https://example.com/ticket-1234.pdf']
        ];
        $result = $twilio->sendWhatsApp($to, $message, $options);
        return response()->json($result);
    }

    public function sendBulkSms(){
        $twilio = new TwilioService();
        $numbers = [
            '+918000912126',
            '+919999999999', // Add more numbers as needed
        ];
        $message = 'Hello, this is a bulk SMS test message from Twilio!';
        $options = [
            'media_urls' => ['https://images.unsplash.com/photo-1545093149-618ce3bcf49d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=668&q=80']
        ];
        $results = $twilio->sendBulkSms($numbers, $message, $options);
        return response()->json($results);
    }
}
