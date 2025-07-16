<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
// Models
use App\Models\Locations\State;
use App\Models\Customers\Customer;

class ViewController extends Controller
{
    
     /**
     * Show the form for view the specified product option.
     *
     * @param string $unique_id
     * @return View
     */
    public function __invoke(string $unique_id): View
    {
        
        $customer = Customer::with('orders','accountApprovedBy', 'taxStatusApprovedBy' , 'addresses.state', 'billingAddress', 'shippingAddress','media')
        ->where('unique_id', $unique_id)
        ->firstOrFail();

         $states = State::get();


        return view('admin.crm.customers.view', [
            'customer' => $customer,
            'states' => $states,

        ]);
    }
}
