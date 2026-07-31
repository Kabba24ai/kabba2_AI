<?php

namespace App\Http\Controllers\Admin\Tests;

use App\Enums\Communication\SmsType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderTermsStatus;
use App\Helpers\ConfigurationHelper;
use App\Helpers\CustomHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\TwilioService;
use App\Services\AuthorizeNetService;

// Resources
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource;
use App\Jobs\SalesFunnelAfterEventJob;
use App\Models\Customers\CustomerCard;
use App\Models\Customers\SalesFunnel;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductFunnelLog;
use App\Services\MailService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Str;
use Throwable;

class IndexController extends Controller
{
    /**
     * Remove a fuel charge (BillingCharge + legacy CustomerAccount) for a single order product.
     * GET /admin/test/remove-fuel-charge/{orderProductId}
     */
    public function removeFuelCharge(int $orderProductId)
    {
        $result = DB::transaction(function () use ($orderProductId) {
            $billingCharge = BillingCharge::where('order_product_id', $orderProductId)
                ->where('billing_charge_type', 'fuel')
                ->first();

            $billingChargeId   = null;
            $customerAccountId = null;
            $customerId        = null;

            if ($billingCharge) {
                $billingChargeId   = $billingCharge->id;
                $customerAccountId = $billingCharge->customer_account_id;

                if ($customerAccountId) {
                    $txn = CustomerAccount::find($customerAccountId);

                    if ($txn) {
                        $customerId = $txn->customer_id;
                        CustomHelper::reverseTransactionEffect($txn);
                        $txn->delete();
                        CustomHelper::fixTheRunningBalance($customerId);
                    }
                }

                $billingCharge->delete();
            }

            // Always clear the charge from the order product's own checklist display
            // (the "Customer Owes" column on the Fuel row is read straight from
            // fuel_total_charge, independent of billing_charges/customer_accounts) —
            // this must run even if the BillingCharge/CustomerAccount were already
            // removed by a previous call.
            $orderProduct = OrderProduct::find($orderProductId);
            $checklistCleared = false;

            if ($orderProduct && (float) $orderProduct->fuel_total_charge > 0) {
                $orderProduct->update([
                    'fuel_total_charge' => 0,
                    'fuel_charge_status' => \App\Enums\Orders\OrderProductChargeStatus::Resolved->value,
                ]);
                $checklistCleared = true;
            }

            if (!$billingCharge && !$checklistCleared) {
                return [
                    'success' => false,
                    'message' => "Nothing to remove for order_product_id={$orderProductId} — no fuel BillingCharge and fuel_total_charge is already 0.",
                ];
            }

            return [
                'success'              => true,
                'order_product_id'     => $orderProductId,
                'billing_charge_id'    => $billingChargeId,
                'customer_account_id'  => $customerAccountId,
                'customer_id'          => $customerId,
                'checklist_cleared'    => $checklistCleared,
                'message'              => 'Fuel charge removed' . ($billingChargeId ? ', balance reversed,' : ' (already gone),') . ' and checklist Customer Owes cleared.',
            ];
        });

        return response()->json($result);
    }

    // new method here
    public function termDailyReminder()
    {
        $today = Carbon::now('America/Chicago')->toDateString();

        Order::query()
            ->where('terms_status', OrderTermsStatus::Pending)
            ->whereNull('terms_accepted_at')
            ->whereNull('reference_order_number')
            ->whereHas('products', function ($query) use ($today) {
                $query->where('product_data->product_type', 'Rental')
                    ->whereDate('delivery_date', $today);
            })
            ->select('order_number')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    dump("Dispatching terms reminder for Order ID: {$order->order_number}");
                }
            });
        dd("Term daily reminder process completed for orders with delivery date: {$today}");

    }

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

    public function paymentProfileCardInfo(Request $request, ?string $paymentProfileId = null)
    {
        $paymentProfileId = $paymentProfileId ?: $request->query('payment_profile_id');

        if (empty($paymentProfileId)) {
            return response()->json([
                'success' => false,
                'message' => 'payment_profile_id is required.',
                'example' => url('/test/payment-profile-card-info/1365271088'),
            ], 422);
        }

        $customerCard = CustomerCard::query()
            ->with('customer:id,authorize_profile_id')
            ->where('payment_profile_id', $paymentProfileId)
            ->first();

        $customerProfileId = $request->query('customer_profile_id') ?: $customerCard?->customer?->authorize_profile_id;

        if (empty($customerProfileId)) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile ID could not be resolved. Pass customer_profile_id as query string.',
                'payment_profile_id' => $paymentProfileId,
                'example' => url('/test/payment-profile-card-info/' . $paymentProfileId) . '?customer_profile_id=525188434',
            ], 422);
        }

        try {
            $cardInfo = (new AuthorizeNetService())->getCardInfoFromPaymentProfile($customerProfileId, $paymentProfileId);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch card details from Authorize.Net.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'payment_profile_id' => $paymentProfileId,
            'customer_profile_id' => $customerProfileId,
            'customer_card_unique_id' => $customerCard?->unique_id,
            'card_number' => $cardInfo['card_number'] ?? null,
            'card_type' => $cardInfo['card_type'] ?? null,
        ]);
    }

    public function backfillCustomerCardInfo(Request $request)
    {
        @set_time_limit(0);

        $limit = max((int) $request->query('limit', 0), 0);
        $dryRun = $request->boolean('dry_run', false);

        $totalNullCardNumber = CustomerCard::query()
            ->whereNull('card_number')
            ->count();

        $eligibleNullCardNumber = CustomerCard::query()
            ->whereNull('card_number')
            ->whereNotNull('payment_profile_id')
            ->count();

        $missingPaymentProfileId = CustomerCard::query()
            ->whereNull('card_number')
            ->whereNull('payment_profile_id')
            ->count();

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $samples = [];
        $failures = [];
        $authorizeNetService = new AuthorizeNetService();
        $remaining = $limit > 0 ? $limit : null;

        CustomerCard::query()
            ->with('customer:id,authorize_profile_id')
            ->whereNull('card_number')
            ->whereNotNull('payment_profile_id')
            ->orderBy('id')
            ->chunkById(25, function ($cards) use (
                &$processed,
                &$updated,
                &$skipped,
                &$failed,
                &$samples,
                &$failures,
                &$remaining,
                $dryRun,
                $authorizeNetService
            ) {
                foreach ($cards as $card) {
                    if ($remaining !== null && $remaining <= 0) {
                        return false;
                    }

                    $processed++;

                    if ($remaining !== null) {
                        $remaining--;
                    }

                    $customerProfileId = $card->customer?->authorize_profile_id;

                    if (empty($customerProfileId)) {
                        $skipped++;

                        if (count($failures) < 25) {
                            $failures[] = [
                                'customer_card_id' => $card->id,
                                'payment_profile_id' => $card->payment_profile_id,
                                'reason' => 'Missing customer authorize_profile_id.',
                            ];
                        }

                        continue;
                    }

                    try {
                        $cardInfo = $authorizeNetService->getCardInfoFromPaymentProfile($customerProfileId, $card->payment_profile_id);
                    } catch (Throwable $exception) {
                        report($exception);

                        $failed++;

                        if (count($failures) < 25) {
                            $failures[] = [
                                'customer_card_id' => $card->id,
                                'payment_profile_id' => $card->payment_profile_id,
                                'customer_profile_id' => $customerProfileId,
                                'reason' => $exception->getMessage(),
                            ];
                        }

                        continue;
                    }

                    $cardNumber = $cardInfo['card_number'] ?? null;
                    $cardType = $cardInfo['card_type'] ?? null;

                    if (empty($cardNumber) && empty($cardType)) {
                        $skipped++;

                        if (count($failures) < 25) {
                            $failures[] = [
                                'customer_card_id' => $card->id,
                                'payment_profile_id' => $card->payment_profile_id,
                                'customer_profile_id' => $customerProfileId,
                                'reason' => 'Authorize.Net returned empty card details.',
                            ];
                        }

                        continue;
                    }

                    if (!$dryRun) {
                        $card->forceFill([
                            'card_number' => $cardNumber ?: $card->card_number,
                            'card_type' => $cardType ?: $card->card_type,
                        ])->save();
                    }

                    $updated++;

                    if (count($samples) < 25) {
                        $samples[] = [
                            'customer_card_id' => $card->id,
                            'unique_id' => $card->unique_id,
                            'payment_profile_id' => $card->payment_profile_id,
                            'customer_profile_id' => $customerProfileId,
                            'card_number' => $cardNumber,
                            'card_type' => $cardType,
                        ];
                    }
                }

                return $remaining === null || $remaining > 0;
            });

        return response()->json([
            'success' => true,
            'dry_run' => $dryRun,
            'limit' => $limit ?: null,
            'total_null_card_number_before' => $totalNullCardNumber,
            'eligible_null_card_number_before' => $eligibleNullCardNumber,
            'missing_payment_profile_id_before' => $missingPaymentProfileId,
            'processed' => $processed,
            $dryRun ? 'would_update' : 'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'total_null_card_number_after' => CustomerCard::query()->whereNull('card_number')->count(),
            'samples' => $samples,
            'failures' => $failures,
        ]);
    }

    public function backfillOrderProductCleaningFields(Request $request)
    {
        @set_time_limit(0);

        $dryRun = $request->boolean('dry_run', false);

        $processed = 0;
        $updated = 0;
        $samples = [];

        OrderProduct::query()
            ->with('product:id,rental_prepaid_cleaning')
            ->orderBy('id')
            ->chunkById(100, function ($orderProducts) use (&$processed, &$updated, &$samples, $dryRun) {
                foreach ($orderProducts as $orderProduct) {
                    $processed++;

                    $productData = $orderProduct->product_data ?? [];

                    if (array_key_exists('rental_prepaid_cleaning', $productData)) {
                        $rentalPrepaidCleaning = (float) $productData['rental_prepaid_cleaning'];
                    } else {
                        $rentalPrepaidCleaning = (float) ($orderProduct->product?->rental_prepaid_cleaning ?? 0);
                        $productData['rental_prepaid_cleaning'] = $rentalPrepaidCleaning;
                    }

                    $isProductClean = $rentalPrepaidCleaning > 0;
                    $productData['is_product_clean'] = $isProductClean;

                    if (count($samples) < 20) {
                        $samples[] = [
                            'id' => $orderProduct->id,
                            'rental_prepaid_cleaning' => $rentalPrepaidCleaning,
                            'is_product_clean' => $isProductClean,
                        ];
                    }

                    if ($dryRun) {
                        continue;
                    }

                    $orderProduct->update([
                        'rental_prepaid_cleaning' => $rentalPrepaidCleaning,
                        'is_product_clean' => $isProductClean,
                        'product_data' => $productData,
                    ]);

                    $updated++;
                }
            });

        return response()->json([
            'dry_run' => $dryRun,
            'processed' => $processed,
            $dryRun ? 'would_update' : 'updated' => $updated,
            'samples' => $samples,
        ]);
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
