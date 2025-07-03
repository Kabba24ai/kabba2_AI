<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

// Models
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;

use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;

class EditController extends Controller
{
    /**
     * Show the form for editing the specified product option.
     *
     * @param string $unique_id
     * @return View
     */
    public function __invoke(string $unique_id): View
    {
       
        $customer = Customer::with('orders','addresses', 'billingAddress', 'shippingAddress','media')
        ->where('unique_id', $unique_id)
        ->firstOrFail();

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
        
        $admins = User::get();

        $states = State::get();

        $customeraddresses = CustomerAddress::with('state')->where('customer_id',$customer->id)->get()  ;

        
    $addressListJson = $customeraddresses->map(function ($address) {
        return [
            'address_id' => $address->id ,
            'first_name' => $address->first_name ?? '',
            'last_name' => $address->last_name ?? '',
            'email' => $address->email ?? '',
            'phone' => $address->phone ?? '',
            'type' => $address->type ?? '',
            'address' => $address->address ?? '',
            'city' => $address->city ?? '',
            'state' => $address->state->name ?? '',
            'state_id' => $address->state_id ?? '',
            'zip_code' => $address->zip_code ?? '',
            'label' => $address->type === 'Billing' ? 'Billing Address' : 'Shipping Address',
        ];
    })->values()->toJson();


    

        return view('admin.crm.customer_portal.edit', [
            'customer' => $customer,
            'admins' => $admins ,
            'states' => $states ,
            'addressListJson' => old('alladdresslist') ?? $addressListJson,
            'website_protocol'=> $protocol,
            'company_website'=> $domain,
            'website_extension'=> $extension,
        ]);
    }
}
