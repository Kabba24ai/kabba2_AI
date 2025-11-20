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
            Setting::where('setting_name', $key)
                ->where('setting_type', 'Invoice Settings')
                ->update(['setting_value' => $value]);
        }

        flash()->success(__('Invoice settings updated successfully.'));
        return redirect()->back();
    }
}
