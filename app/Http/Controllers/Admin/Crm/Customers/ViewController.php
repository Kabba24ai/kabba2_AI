<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\ConfigurationHelper;
use App\Helpers\CustomHelper;
// Models
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use Illuminate\View\View;
use App\Models\Customers\Tag;   

class ViewController extends Controller
{
    /**
     * Show the form for view the specified product option.
     */
    public function __invoke(string $unique_id): View
    {
        $employees = User::orderBy('first_name')->get();

        $customer = Customer::with('orders.products', 'orders.payments',
            'notes.user',
            'invoices.items', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        CustomHelper::markOverdueInvoices($customer->id);

        $lastpaymentdate = $customer->accounts()
            ->where('type', 'payment')
            ->orderByDesc('date')
            ->value('date');

        // dd($customer->total_order_amount);

        $states = State::get();

        $protocol = '';
        $domain = '';
        $extension = '';

        if (! empty($customer->company_website)) {
            $parsedUrl = parse_url($customer->company_website);

            // Extract protocol
            $protocol = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'].'://' : '';

            // Extract host (domain + extension)
            if (! empty($parsedUrl['host'])) {
                $hostParts = explode('.', $parsedUrl['host']);

                if (count($hostParts) >= 2) {
                    $extension = '.'.array_pop($hostParts); // e.g. .com
                    $domain = implode('.', $hostParts);       // e.g. example
                }
            }
        }

        $users = User::where('status', 'Active')->get();
        // biling sumary

        $query = Customer::with('orders.payments', 'addresses', 'accounts')->whereIn('status', ['Active', 'Inactive']);

        $customers = $query->latest('id')->paginate(10)->withQueryString();

        // biling sumary
        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        // Load tag objects
        $customer->tag_objects = $customer->tag_objects ?? [];

        $allTags = $customer->tag_objects ?? [];

       // Convert customer tag objects into list of names
$customerTagNames = collect($allTags)->pluck('name')->toArray();

// ALL tags from DB EXCEPT customer's current tags
$existingTagNames = Tag::whereNotIn('name', $customerTagNames)
                        ->orderBy('name')
                        ->pluck('name')
                        ->toArray();

        return view('admin.crm.customers.view', [
            'customer' => $customer,
            'states' => $states,
            'website_protocol' => $protocol,
            'company_website' => $domain,
            'website_extension' => $extension,
            'users' => $users,
            'lastpaymentdate' => $lastpaymentdate,
            'customers' => $customers,
            'paymentSetting' => $paymentSetting,
            'employees' => $employees,
            'allTags' => $allTags,
            'existingTagNames' => $existingTagNames,
            'customerTagNames' => $customerTagNames,

        ]);

    }
}
