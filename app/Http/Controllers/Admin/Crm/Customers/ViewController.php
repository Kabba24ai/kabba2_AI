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

           $protocol = '';
        $domain = '';
        $extension = '';

        if (!empty($customer->company_website)) {
            $parsedUrl = parse_url($customer->company_website);

            // Extract protocol
            $protocol = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';

            // Extract host (domain + extension)
            if (!empty($parsedUrl['host'])) {
                $hostParts = explode('.', $parsedUrl['host']);

                if (count($hostParts) >= 2) {
                    $extension = '.' . array_pop($hostParts); // e.g. .com
                    $domain = implode('.', $hostParts);       // e.g. example
                }
            }
        }
        


        return view('admin.crm.customers.view', [
            'customer' => $customer,
            'states' => $states,
            'website_protocol'=> $protocol,
            'company_website'=> $domain,
            'website_extension'=> $extension,
        ]);
    }
}
