<?php

    namespace App\Http\Controllers\Front\Customer\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customers\Customer;
use App\Models\Locations\State;
use App\Helpers\ConfigurationHelper;
use App\Helpers\CustomHelper;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {


        $customer = Auth::guard('customer')->user() ;


        CustomHelper::markOverdueInvoices($customer->id);


        $customer->load('addresses.state');

        $lastpaymentdate = $customer->accounts()
            ->where('type', 'payment')
            ->orderByDesc('date')
            ->value('date');

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
        // biling sumary
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        return view('front.customer.dashboard.index', [
            'title' => 'Home - Customer Dashboard',
            'customer' => $customer,
            'lastpaymentdate' => $lastpaymentdate ,
            'states' => $states,
            'website_protocol'=> $protocol,
            'company_website'=> $domain,
            'website_extension'=> $extension,
            'paymentSetting' => $paymentSetting ,
        ]);
    }
}
