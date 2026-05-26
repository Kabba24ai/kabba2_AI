<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Steps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\SalesFunnels\Steps\UpdateRequest;
use App\Models\Customers\SalesFunnelSteps;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\SmsFunnel;
use App\Models\Customers\EmailCategory;
use App\Models\Customers\EmailTemplate;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $stepUniqueId)
    {
        $data = $request->validated();

        try {
            $step = SalesFunnelSteps::where('unique_id', $stepUniqueId)->firstOrFail();

            switch ($data['step_type']) {
                case 'SMS':
                    $smsCategory = SmsCategory::find($data['message_category_id']);
                    $smsMessage  = SmsFunnel::find($data['message_template_id']);

                    if (! $smsCategory || ! $smsMessage) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid SMS category or message template selected.',
                        ], 422);
                    }

                    $data['sms_category_id'] = $smsCategory->id;
                    $data['sms_message_id']  = $smsMessage->id;
                    $data['name']            = $smsMessage->name;
                    $data['message']         = $smsMessage->description;
                    break;

                case 'Email':
                    $emailCategory = EmailCategory::find($data['message_category_id']);
                    $emailTemplate = EmailTemplate::find($data['message_template_id']);

                    if (! $emailCategory || ! $emailTemplate) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid email category or template selected.',
                        ], 422);
                    }

                    $data['sms_category_id'] = null;
                    $data['sms_message_id']  = null;
                    $data['name']            = $emailTemplate->name;
                    $data['message']         = $emailTemplate->subject;
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported step type.',
                    ], 422);
            }

            // Compute total offset in minutes from the three parts
            $data['offset_minutes'] = ((int) $data['offset_days']        * 1440)
                                    + ((int) $data['offset_hours']       * 60)
                                    + ((int) $data['offset_minutes_val']);

            $step->update($data);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sales funnel step: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel step updated successfully.',
        ]);
    }
}
