<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;


class IndexController extends Controller
{

    public function __invoke(Request $request ,$unique_id)
    {
        
        // session()->flash('active_tab', 'invoices');
session(['active_tab' => 'invoices']);


        return redirect()->route('admin.crm.customers.view', $unique_id) ;
    }
}
