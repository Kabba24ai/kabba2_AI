<?php

namespace App\Http\Controllers\Admin\Tests;

use App\Http\Controllers\Controller;
use App\Services\TwilioService;

class IndexController extends Controller
{
    // new method here

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
