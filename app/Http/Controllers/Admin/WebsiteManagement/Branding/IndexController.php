<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Branding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;

use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\SupplierTag;



class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
                      return view('admin.website_management.faq_page.index');

    }
}
