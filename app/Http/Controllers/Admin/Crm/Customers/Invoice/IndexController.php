<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

// Models


class IndexController extends Controller
{

    public function __invoke(Request $request ,$unique_id)
    {

        // flash('Customer updated successfully.')->success();
        session()->flash('active_tab', 'invoices');

        return redirect()->route('admin.crm.customers.view', $unique_id) ;

    }
}
