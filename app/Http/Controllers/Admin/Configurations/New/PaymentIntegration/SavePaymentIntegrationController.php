<?php

namespace App\Http\Controllers\Admin\Configurations\New\PaymentIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\New\PaymentIntegration\PaymentIntegrationRequest;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\DB;

class SavePaymentIntegrationController extends Controller
{
    public function __invoke(PaymentIntegrationRequest $request)
    {
        $validated = $request->validated();

        // Define allowed settings mapping
        
        $map = [
            'payment_gateway' => 'payment_gateway',
            'payment_api_public_key' => 'payment_api_public_key',
            'payment_api_key' => 'payment_api_key',
            'payment_api_secret' => 'payment_api_secret',
            'payment_test_mode' => 'payment_test_mode',
        ];

        DB::transaction(function () use ($validated, $map) {
            foreach ($validated as $input => $value) {
                if (isset($map[$input])) {
                    Setting::updateOrCreate(
                        ['setting_name' => $map[$input]],
                        ['setting_value' => $value]
                    );
                }
            }
        });

        flash()->success(__('Payment Integration settings updated successfully.'));
        return redirect()->back();
    }
}
