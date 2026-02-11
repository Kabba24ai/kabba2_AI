<?php

namespace App\Http\Controllers\Admin\Configurations\InvoiceSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\InvoiceSettings\SaveRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

       foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Invoice Settings',
                ],
                [
                    'setting_value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }


        flash()->success(__('Invoice settings updated successfully.'));
        return redirect()->back();
    }
}
