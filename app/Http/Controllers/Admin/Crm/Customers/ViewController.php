<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\ConfigurationHelper;
use App\Helpers\CustomHelper;
// Models
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\Tag;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Services\CustomerCreditService;
use Illuminate\View\View;

class ViewController extends Controller
{
    /**
     * Show the form for view the specified product option.
     */
    public function __invoke(string $unique_id): View
    {
        $customer = Customer::with([
            'orders.products', 'orders.payments',
            'notes' => fn ($q) => $q->with('user')->orderByDesc('created_at'),
            'invoices' => fn ($q) => $q->with('items')->orderByDesc('invoice_date'),
            'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media',
            'customerCredits.responsibleUser',
        ])
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        $assignedUserIds = collect()
            ->merge($customer->accounts->pluck('responsible_person_id'))
            ->merge($customer->notes->pluck('user_id'))
            ->merge($customer->invoices->flatMap(fn ($invoice) => $invoice->items->pluck('responsible_person_id')))
            ->filter()
            ->unique()
            ->values();

        $users = User::activeOrIds($assignedUserIds)->orderBy('first_name')->get();
        $employees = $users;

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

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        // ALL tags from DB EXCEPT customer's current tags
        $existingTagNames = Tag::whereNotIn('name', $customerTagNames)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        // dd($customer->accounts);

        $funnels = SalesFunnel::active()->orderBy('funnel_name')->pluck('funnel_name', 'id');

        // Phase 3.1 — Customer Credit Administration. Balance figures come
        // from CustomerCreditService (the single source of truth); running
        // balance in the history table is computed here for display only,
        // never persisted, avoiding the cached-value drift this initiative
        // has repeatedly found and eliminated elsewhere.
        $customerCreditSummary = [
            'balance' => CustomerCreditService::remainingBalance($customer->id),
            'lifetime_granted' => (float) $customer->customerCredits->where('type', CustomerCreditService::TYPE_GRANT)->sum('amount'),
            'lifetime_redeemed' => (float) $customer->customerCredits->where('type', CustomerCreditService::TYPE_REDEMPTION)->sum('amount'),
        ];

        $runningBalance = 0.0;
        $customerCreditHistory = $customer->customerCredits->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry->type === CustomerCreditService::TYPE_GRANT ? (float) $entry->amount : -(float) $entry->amount;

            return [
                'date' => $entry->created_at->format('Y-m-d'),
                'date_display' => $entry->created_at->format('M d, Y'),
                'type' => $entry->type,
                'amount' => (float) $entry->amount,
                'running_balance' => round($runningBalance, 2),
                'user' => $entry->responsible_person_name ?? optional($entry->responsibleUser)->full_name ?? 'System',
                'reason' => $entry->reason,
                'notes' => $entry->notes ?? '',
            ];
        })->values()->all();

        return view('admin.crm.customers.view', [
            'customer' => $customer,
            'states' => $states,
            'funnels' => $funnels,
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
            'sales_tax' => $sales_tax,
            'customerCreditSummary' => $customerCreditSummary,
            'customerCreditHistory' => $customerCreditHistory,

        ]);

    }
}
