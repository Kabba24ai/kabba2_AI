<?php

namespace App\Http\Controllers\Admin\Configurations\New\ContactUsSettings;

use App\Http\Controllers\Controller;

// Models
use App\Models\Configurations\Setting;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'mobile'          => 'required|string|max:20',
            'email'           => 'required|email|max:255',
            'address1'        => 'nullable|string|max:255',
            'address2'        => 'nullable|string|max:255',
            'enquiry-email'   => 'nullable|email|max:255',
            'complaint-email' => 'nullable|email|max:255',
            'feedback-email'  => 'nullable|email|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::where('setting_name', $key)
                ->where('setting_type', 'Contact Us Settings')
                ->update(['setting_value' => $value]);
        }


        flash()->success(__('Settings updated successfully.'));
        return redirect()->back();
    }
}
