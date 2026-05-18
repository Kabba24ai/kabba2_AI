<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactUs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;

use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\SupplierTag;
use App\Models\Configurations\Setting;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $settings = Setting::where('setting_type', 'Website Management Contact Us Section')->pluck('setting_value', 'setting_name')->toArray();

        return view('admin.website_management.contact_us.index', [
            'settings' => $settings,
        ]);
    }
}
