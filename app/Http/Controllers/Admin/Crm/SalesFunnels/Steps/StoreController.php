<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Steps;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Crm\SalesFunnels\Steps\StoreRequest;

// Models
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SalesFunnelSteps;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\SmsFunnel;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        // Validation passed, proceed with storing the sales funnel step
        $data = $request->validated();

        try {
            $salesFunnel = SalesFunnel::where('unique_id', $data['funnel_unique_id'])->firstOrFail();
            if($salesFunnel){
                $data['sales_funnel_id'] = $salesFunnel->id;
            }else{
                $data['sales_funnel_id'] = null;
            }

            $smsCategory = SmsCategory::where('id', $data['sms_category_id'])->first();
            if ($smsCategory) {
                $data['sms_category_id'] = $smsCategory->id;
            }else{
                $data['sms_category_id'] = null;
            }

            $smsMessage = SmsFunnel::where('id', $data['sms_funnel_id'])->first();
            if ($smsMessage) {
                $data['sms_message_id'] = $smsMessage->id;
                $data['name'] = $smsMessage->name;
                $data['message'] = $smsMessage->description;
            }else{
                $data['sms_message_id'] = null;
            }

            SalesFunnelSteps::create($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sales funnel step: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel step created successfully.',
        ]);
    }
}
