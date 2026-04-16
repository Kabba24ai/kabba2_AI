<?php

namespace App\Http\Controllers\Admin\Tests;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\TwilioService;

// Resources
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource;
use App\Jobs\SalesFunnelAfterEventJob;
use App\Models\Customers\SalesFunnel;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductFunnelLog;
use App\Services\MailService;
use App\Services\OpenAIService;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Str;

class IndexController extends Controller
{
    // new method here
     public function salesFunnelStepAfterEvent()
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));
        // 15-min cron: align window to the current 15-min block
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd   = $windowStart->copy()->addMinutes(15);

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->afterEvent()
            ->whereHas('products')
            ->with(['products:id', 'steps'])
            ->get();

        foreach ($funnels as $funnel) {
            $productIds = $funnel->products->pluck('id');
            $steps = $funnel->steps->where('step_type', 'SMS')->sortBy('id')->values();

            if ($steps->isEmpty()) {
                $steps = collect([(object) [
                    'id' => null,
                    'step_type' => 'SMS',
                    'name' => null,
                    'message' => $funnel->description,
                    'delay_unit' => 'Minutes',
                    'delay_value' => ((int) ($funnel->date_value ?? 0) * 1440) + ((int) ($funnel->hour_value ?? 0) * 60) + (int) ($funnel->minute_value ?? 0),
                ]]);
            }

            $cumulativeOffsetMinutes = 0;
            foreach ($steps as $step) {
                $delayValue = (int) ($step->delay_value ?? 0);

                $stepOffsetMinutes = match ($step->delay_unit) {
                    'Days' => $delayValue * 1440,
                    'Hours' => $delayValue * 60,
                    default => $delayValue,
                };

                // Add current step's delay to cumulative total
                $cumulativeOffsetMinutes += $stepOffsetMinutes;
                $offsetMinutes = $cumulativeOffsetMinutes;

                $startDelivery = $windowStart->copy()->subMinutes($offsetMinutes);
                $endDelivery   = $windowEnd->copy()->subMinutes($offsetMinutes);

                // $records = OrderProduct::query()
                //     ->with(['order.customer', 'order.shippingAddress'])
                //     ->whereIn('product_id', $productIds)
                //     ->where('delivery_status', 'Pending')

                //     // not already processed for this funnel step
                //     ->whereDoesntHave('funnelLogs', function ($q) use ($funnel, $step) {
                //         $q->where('sales_funnel_id', $funnel->id);

                //         if ($step->id) {
                //             $q->where('sales_funnel_step_id', $step->id);
                //         } else {
                //             $q->whereNull('sales_funnel_step_id');
                //         }
                //     })

                //     ->whereBetween(DB::raw('TIMESTAMP(order_products.delivery_date, order_products.delivery_time)'), [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()])
                //     ->get();

                // dump($step->toArray(), $startDelivery->format('Y-m-d h:i:s a'), $endDelivery->format('Y-m-d h:i:s a'), $now->format('Y-m-d h:i:s a'), $productIds, $records->toArray());

                // foreach ($records as $record) {
                //     $phoneNumber = $record->order->shippingAddress->phone ?? $record->order->customer_phone;
                //     $message = $step->message ?: $funnel->description;

                //     try {
                //         $response = $twilio->sendSms($phoneNumber, $message, [], [
                //             'order_id'         => $record->order_id,
                //             'order_product_id' => $record->id,
                //             'customer_id'      => optional($record->order)->customer_id,
                //             'sms_type'         => SmsType::SALES_FUNNEL_AFTER,
                //         ]);

                //         OrderProductFunnelLog::create([
                //             'order_product_id' => $record->id,
                //             'sales_funnel_id' => $funnel->id,
                //             'sales_funnel_step_id' => $step->id,
                //             'step_type' => $step->step_type,
                //             'step_name' => $step->name,
                //             'product_id' => $record->product_id,
                //             'message' => $message,
                //             'status' => ($response['success'] ?? false) ? 'Sent' : 'Failed',
                //             'sent_at' => Carbon::now(),
                //         ]);

                //         if (!($response['success'] ?? false)) {
                //             \Log::channel('sales_funnel')->warning("Failed to send SMS. OP={$record->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error=" . ($response['message'] ?? 'unknown'));
                //         } else {
                //             \Log::channel('sales_funnel')->info("Sent SMS. OP={$record->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy'));
                //         }

                //     } catch (\Exception $e) {
                //         OrderProductFunnelLog::create([
                //             'order_product_id' => $record->id,
                //             'sales_funnel_id' => $funnel->id,
                //             'sales_funnel_step_id' => $step->id,
                //             'step_type' => $step->step_type,
                //             'step_name' => $step->name,
                //             'product_id' => $record->product_id,
                //             'message' => $message,
                //             'status' => 'Failed',
                //             'sent_at' => Carbon::now(),
                //         ]);

                //         \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$record->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error={$e->getMessage()}");
                //         continue;
                //     }
                // }

                OrderProduct::query()
                    ->with(['order.customer', 'order.shippingAddress'])
                    ->whereIn('product_id', $productIds)
                    ->where('delivery_status', 'Pending')

                    // not already processed for this funnel step
                    ->whereDoesntHave('funnelLogs', function ($q) use ($funnel, $step) {
                        $q->where('sales_funnel_id', $funnel->id);

                        if ($step->id) {
                            $q->where('sales_funnel_step_id', $step->id);
                        } else {
                            $q->whereNull('sales_funnel_step_id');
                        }
                    })

                    ->whereBetween(DB::raw('TIMESTAMP(order_products.delivery_date, order_products.delivery_time)'), [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()])

                    ->chunkById(500, function ($orderProducts) use ($funnel, $step, $twilio) {
                        foreach ($orderProducts as $op) {
                            $customer = $op->order->customer;
                            $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone;

                            $message = $step->message ?: $funnel->description;

                            try {
                                $response = $twilio->sendSms($phoneNumber, $message, [], [
                                    'order_id'         => $op->order_id,
                                    'order_product_id' => $op->id,
                                    'customer_id'      => $customer?->id,
                                    'sms_type'         => SmsType::SALES_FUNNEL_AFTER,
                                ]);

                                OrderProductFunnelLog::create([
                                    'order_product_id' => $op->id,
                                    'sales_funnel_id' => $funnel->id,
                                    'sales_funnel_step_id' => $step->id,
                                    'step_type' => $step->step_type,
                                    'step_name' => $step->name,
                                    'product_id' => $op->product_id,
                                    'message' => $message,
                                    'status' => ($response['success'] ?? false) ? 'Sent' : 'Failed',
                                    'sent_at' => Carbon::now(),
                                ]);

                                if (!($response['success'] ?? false)) {
                                    \Log::channel('sales_funnel')->warning("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error=" . ($response['message'] ?? 'unknown'));
                                } else {
                                    \Log::channel('sales_funnel')->info("Sent SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy'));
                                }

                            } catch (\Exception $e) {
                                OrderProductFunnelLog::create([
                                    'order_product_id' => $op->id,
                                    'sales_funnel_id' => $funnel->id,
                                    'sales_funnel_step_id' => $step->id,
                                    'step_type' => $step->step_type,
                                    'step_name' => $step->name,
                                    'product_id' => $op->product_id,
                                    'message' => $message,
                                    'status' => 'Failed',
                                    'sent_at' => Carbon::now(),
                                ]);

                                \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error={$e->getMessage()}");
                                continue;
                            }
                        }
                    });
            }
        }

        return response()->json([
            'current_time' => $now->toDateTimeString(),
            'window_start' => $windowStart->toDateTimeString(),
            'window_end'   => $windowEnd->toDateTimeString(),
        ]);
    }

    public function sendTestEmail()
    {

        $to = 'rajkc.webdev@gmail.com'; // Replace with your test email address
        $subject = 'Test Email from IndexController';
        $body = 'This is a test email sent from the IndexController.';

        try {
            $settings = (new MailService())->getSettings();

            $email = (new Email())
            ->from(new Address($settings['from']['address'], $settings['from']['name']))
            ->to($to)
            ->subject($subject)
            ->text($body);

            $dsn = sprintf(
            '%s://%s:%s@%s:%s',
            $settings['mailer'],
            urlencode($settings['username']),
            urlencode($settings['password']),
            $settings['host'],
            $settings['port']
            );



            $mailer = new Mailer(Transport::fromDsn($dsn));
            $mailer->send($email);

            /**
             * Example of sending a normal email using Laravel's Mail facade.
             */
            // \Mail::raw('This is a normal test email sent using Laravel Mail facade.', function ($message) use ($to) {
            //     $message->to($to)
            //         ->subject('Test Email from Laravel Mail Facade');
            // });

            return response()->json(['status' => 'Test email sent using MailService.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'Failed to send test email.', 'error' => $e->getMessage()], 500);
        }
    }

    public function salesFunnelAfterEventJob()
    {
        // Dispatch the SalesFunnelAfterEventJob
        SalesFunnelAfterEventJob::dispatch();
        return response()->json(['status' => 'Sales Funnel After Event Job dispatched.']);
    }

    public function salesFunnelBeforeEventJob()
    {
        // Dispatch the SalesFunnelBeforeEventJob
        \App\Jobs\SalesFunnelBeforeEventJob::dispatch();
        return response()->json(['status' => 'Sales Funnel Before Event Job dispatched.']);
    }

    public function salesFunnelAfterEvent()
    {
        $now = Carbon::now('Asia/Kolkata');
        // 15-min cron: align window to the current 15-min block
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd   = $windowStart->copy()->addMinutes(15);

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->afterEvent()
            ->whereHas('products')
            ->with(['products:id']) // load only product ids
            ->get();

        foreach ($funnels as $funnel) {
            $days    = (int) ($funnel->date_value ?? 0);
            $hours   = (int) ($funnel->hour_value ?? 0);
            $minutes = (int) ($funnel->minute_value ?? 0);

            $offsetMinutes = ($days * 1440) + ($hours * 60) + $minutes;

            // AFTER EVENT:
            // send_at = delivery_at + offset
            // send_at in [windowStart, windowEnd]
            // => delivery_at in [windowStart - offset, windowEnd - offset]
            $startDelivery = $windowStart->copy()->subMinutes($offsetMinutes);
            $endDelivery   = $windowEnd->copy()->subMinutes($offsetMinutes);

            dd($startDelivery->toDateTimeString(), $endDelivery->toDateTimeString(), $now->toDateTimeString());
            // echo "Processing Funnel ID: {$funnel->id}, Offset Minutes: {$offsetMinutes}\n";
            // echo "Start Delivery Window: " . $startDelivery->toDateTimeString() . "\n";
            // echo "End Delivery Window: " . $endDelivery->toDateTimeString() . "\n";

            $productIds = $funnel->products->pluck('id');

            $orderProducts = OrderProduct::query()
                ->with(['order.customer', 'order.shippingAddress'])
                ->whereIn('product_id', $productIds)
                ->where('delivery_status', 'Pending')

                // not already processed for this funnel
                ->whereDoesntHave('funnelLogs', function ($q) use ($funnel) {
                    $q->where('sales_funnel_id', $funnel->id);
                })

                // THIS is the "before event" timing check
                ->whereBetween(
                    DB::raw("TIMESTAMP(order_products.delivery_date, order_products.delivery_time)"),
                    [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()]
                )
                ->get();

            // $sql = Str::replaceArray(
            //     '?',
            //     collect($orderProducts->getBindings())->map(function ($binding) {
            //         return is_numeric($binding)
            //             ? $binding
            //             : "'{$binding}'";
            //     })->toArray(),
            //     $orderProducts->toSql()
            // );
            // dd($sql);

            foreach ($orderProducts as $op) {
                $customer = $op->order->customer;
                $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone;

                $message = $funnel->description;

                try {
                    $twilio->sendSms($phoneNumber, $message);
                    OrderProductFunnelLog::create([
                        'order_product_id' => $op->id,
                        'sales_funnel_id'  => $funnel->id,
                        'product_id'      => $op->product_id,
                        'message'         => $message,
                        'status'          => 'Sent',
                        'sent_at'         => Carbon::now(),
                    ]);
                } catch (\Exception $e) {
                    OrderProductFunnelLog::create([
                        'order_product_id' => $op->id,
                        'sales_funnel_id'  => $funnel->id,
                        'product_id'      => $op->product_id,
                        'message'         => $message,
                        'status'          => 'Failed',
                        'sent_at'         => Carbon::now(),
                    ]);
                    \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Error={$e->getMessage()}");
                    continue; // Skip logging if SMS fails
                }
                \Log::channel('sales_funnel')->info("Sent SMS. OP={$op->id}, Funnel={$funnel->id}");
            }

        }
        return response()->json([
            'current_time' => $now->toDateTimeString(),
            'window_start' => $windowStart->toDateTimeString(),
            'window_end'   => $windowEnd->toDateTimeString(),
        ]);
    }

    public function salesFunnelBeforeEvent()
    {
        $now = Carbon::now('America/Chicago');
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd   = $now->copy()->floorMinutes(15)->addMinutes(15);

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->beforeEvent()
            ->whereHas('products')
            ->with(['products:id']) // load only product ids
            ->get();

        foreach ($funnels as $funnel) {
            $days    = (int) ($funnel->date_value ?? 0);
            $hours   = (int) ($funnel->hour_value ?? 0);
            $minutes = (int) ($funnel->minute_value ?? 0);

            $offsetMinutes = ($days * 1440) + ($hours * 60) + $minutes;

            // If send_at = delivery_at - offset
            // Then delivery_at must be between (nowWindow + offset)
            $startDelivery = $windowStart->copy()->addMinutes($offsetMinutes);
            $endDelivery   = $windowEnd->copy()->addMinutes($offsetMinutes);

            dd($startDelivery->toDateTimeString(), $endDelivery->toDateTimeString(), $now->toDateTimeString());
            echo "Processing Funnel ID: {$funnel->id}, Offset Minutes: {$offsetMinutes}\n";
            echo "Start Delivery Window: " . $startDelivery->toDateTimeString() . "\n";
            echo "End Delivery Window: " . $endDelivery->toDateTimeString() . "\n";

            $productIds = $funnel->products->pluck('id');

            $orderProducts = OrderProduct::query()
                ->with(['order.customer', 'order.shippingAddress'])
                ->whereIn('product_id', $productIds)
                ->where('delivery_status', 'Pending')

                // not already processed for this funnel
                ->whereDoesntHave('funnelLogs', function ($q) use ($funnel) {
                    $q->where('sales_funnel_id', $funnel->id);
                })

                // THIS is the "before event" timing check
                ->whereBetween(
                    DB::raw("TIMESTAMP(order_products.delivery_date, order_products.delivery_time)"),
                    [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()]
                )
                ->get();

            // $sql = Str::replaceArray(
            //     '?',
            //     collect($orderProducts->getBindings())->map(function ($binding) {
            //         return is_numeric($binding)
            //             ? $binding
            //             : "'{$binding}'";
            //     })->toArray(),
            //     $orderProducts->toSql()
            // );
            // dd($sql);

            foreach ($orderProducts as $op) {
                $customer = $op->order->customer;
                $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone;

                $message = $funnel->description;

                try {
                    $twilio->sendSms($phoneNumber, $message);
                    OrderProductFunnelLog::create([
                        'order_product_id' => $op->id,
                        'sales_funnel_id'  => $funnel->id,
                        'product_id'      => $op->product_id,
                        'message'         => $message,
                        'status'          => 'Sent',
                        'sent_at'         => Carbon::now(),
                    ]);
                } catch (\Exception $e) {
                    OrderProductFunnelLog::create([
                        'order_product_id' => $op->id,
                        'sales_funnel_id'  => $funnel->id,
                        'product_id'      => $op->product_id,
                        'message'         => $message,
                        'status'          => 'Failed',
                        'sent_at'         => Carbon::now(),
                    ]);
                    \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Error={$e->getMessage()}");
                    continue; // Skip logging if SMS fails
                }
                \Log::channel('sales_funnel')->info("Sent SMS. OP={$op->id}, Funnel={$funnel->id}");
            }

        }
        return response()->json([
            'current_time' => $now->toDateTimeString(),
            'window_start' => $windowStart->toDateTimeString(),
            'window_end'   => $windowEnd->toDateTimeString(),
        ]);
    }

    public function equipmentList($type = null)
    {

        $type = $type ?? null;

        // that is use for the ordering of the equipment based on the type
        if($type === 'RentalReady'){
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }else{
            $order = ['available', 'rented', 'maintenance', 'damaged'];
        }

        $equipment = Equipment::with(['productCategory', 'orderProduct','checklistMaster.customerAdminTemplate.templateQuestions.question.answers','checklistMaster.customerAdminTemplate.templateQuestions.question.category','orderProduct.checklistQuestions.answers', 'orderProduct.checklistQuestions.deliverySelectedAnswer', 'orderProduct.checklistQuestions.returnSelectedAnswer'])
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')") // order by current_status based on the defined order
            ->orderByRaw('equipment_name asc')
            ->get();


        $equipment->map(function($item) {
            $isRentedAndDelivered = $item->current_status->isRented()
                && $item->orderProduct
                && ($item->orderProduct->is_delivered == 1);
            if ($isRentedAndDelivered) {
                $questions = optional($item->orderProduct->checklistQuestions) ?? collect();
            } else {
                $templateQuestions = $item->checklistMaster?->customerAdminTemplate?->templateQuestions;
                $questions = collect($templateQuestions)
                    ->pluck('question')
                    ->filter()
                    ->values();
            }
            $item->setRelation('checklistQA', $questions);
            return $item;
        });
        dd($equipment->toArray());
        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.equipment_found'),
            'equipment' => ListResource::collection($equipment),
        ]);
    }

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
    public  function sendCustomFirebaseNotification($token)
    {
        $firebase = new \App\Services\FirebaseService();
        $title = 'Test Notification';
        $body = 'This is a test notification sent from IndexController.';
        $result = $firebase->sendToDevice($token, $title, $body, [
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

    public function testOpenAi(OpenAIService $openAIService)
    {
        try {
            $response = $openAIService->chatCompletion([
                ['role' => 'user', 'content' => 'Say hello in one sentence.'],
            ], [
                'max_tokens' => 50,
            ]);

            $reply = data_get($response, 'choices.0.message.content');

            return response()->json([
                'status'  => 'success',
                'model'   => data_get($response, 'model'),
                'reply'   => $reply,
                'usage'   => data_get($response, 'usage'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
