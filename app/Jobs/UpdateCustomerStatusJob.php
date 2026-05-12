<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

class UpdateCustomerStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Log::info('[CUSTOMER STATUS JOB] Started');

        $customers = Customer::whereIn('status', ['Active', 'Archived'])->get();

        // Log::info('[CUSTOMER STATUS JOB] Total customers', [
        //     'count' => $customers->count()
        // ]);

        foreach ($customers as $customer) {

            $account = CustomHelper::getCustomerAccountStatus($customer);

            // Log::info('[CUSTOMER STATUS JOB] Checking customer====', [
            //     'customer_id' => $customer->id,
            //     'customer_name' => $customer->full_name,
            //     'current_status' => $customer->status,
            //     'account_status' => $account['status']
            // ]);

            //  If Bad Debt → Suspend
            if ($account['status'] == 'Bad Debt') {

                if ($customer->status !== 'Archived') {

                    $customer->status = 'Archived';
                    $customer->save();

                    // Log::warning('[CUSTOMER STATUS JOB] Customer Archived', [
                    //     'customer_id' => $customer->id
                    // ]);
                }

            }
        }

        // Log::info('[CUSTOMER STATUS JOB] Completed');
    }
}
