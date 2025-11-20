<?php

namespace App\Http\Controllers\Admin\Reports\SalesTax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class PaymentViewController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {

        session()->flash('active_tab', 'credit');

        return redirect()->route('admin.crm.customers.view', $unique_id);

    }
}
