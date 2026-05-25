<?php

namespace App\Http\Controllers\Front\ContactUs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stores\Store;
use App\Models\Configurations\Setting;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stores = Store::with('state')->active()->get();

        $contact_settings = Setting::where('setting_type', 'Website Management Contact Us Section')->pluck('setting_value', 'setting_name')->toArray();

        return view('front.contact_us.index', [
            'title' => 'Contact',
            'stores' => $stores,
            'contact_settings' => $contact_settings,
        ]);
    }
}
