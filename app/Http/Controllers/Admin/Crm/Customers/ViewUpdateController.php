<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\ViewUpdateRequest;



use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class ViewUpdateController extends Controller
{
    /**
     * Handle the incoming request to update a customer.
     */
    public function __invoke(ViewUpdateRequest $request, string $unique_id)
    {

        $validated = $request->validated();

        // dd($validated);

        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        DB::beginTransaction();

        try {

            $customerData = [
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,

                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,
                'tax_document_valid_until' => CustomHelper::parseDateFromInput($validated['tax_document_valid_until'] ?? null),
                'tax_document_upload_date' => CustomHelper::parseDateFromInput($validated['tax_document_upload_date'] ?? null),
                'is_credit_account' => $validated['is_credit_account'] ?? false,
                 'tax_status' => $validated['tax_status'] ?? 'Taxable',

                    'credit_limit' => $validated['credit_limit'] ?? null,

                'same_as_billing' => !empty($validated['sameAsBilling']) ? 1 : 0,

                'tags' => isset($validated['tags']) ? json_encode($validated['tags']) : null,


            ];


            // Only set company_website if it has a value
            if (!empty($validated['company_website'])) {
                $fullWebsite = trim(
                    ($validated['website_protocol'] ?? '') .
                    $validated['company_website'] .
                    ($validated['website_extension'] ?? '')
                );

            }
                $customerData['company_website'] = $fullWebsite ?? '';
            $customer->update($customerData);

            // Process address list
                if (!empty($validated['alladdresslist'])) {
                    $submittedAddresses = collect(json_decode($validated['alladdresslist'], true));

                     $typesToUpdate = $submittedAddresses->pluck('type')->unique();

                     // Set is_primary = 0 for all existing addresses of the submitted types
                        CustomerAddress::where('customer_id', $customer->id)
                            ->update(['is_primary' => 0]);


                    //  Loop through submitted and update/create
                    foreach ($submittedAddresses as $address) {
                        $data = [
                            'first_name'    => $address['first_name'] ?? null,
                            'last_name'     => $address['last_name'] ?? null,
                            'type' => $address['type'] ?? null,
                            'phone'         => $address['phone'] ?? null,
                            'email'         => $address['email'] ?? null,
                            'address'       => $address['address'] ?? null,
                            'city'          => $address['city'] ?? null,
                            'state_id' => !empty($address['state_id']) ? (int)$address['state_id'] : null,
                            'zip_code'      => $address['zip_code'] ?? null,
                        'country'     => $address['Country'] ?? null,
                        'customer_id'   => $customer->id,
                            'is_primary'  => 1 ,
                        ];

                        if (!empty($address['address_id'])) {
                            // update
                            CustomerAddress::where('id', $address['address_id'])
                                ->where('customer_id', $customer->id)
                                ->update($data);
                        } else {
                            // create
                            CustomerAddress::create($data);
                        }
                    }
                }


            DB::commit();

            flash('Customer updated successfully.')->success();
            // session()->flash('active_tab', 'account');
session(['active_tab' => 'account']);
            return redirect()
           ->back() ;


        } catch (\Throwable $e) {
           DB::rollBack();
        report($e);

        flash('Something went wrong while updating the customer.'.$e)->error();
        // session()->flash('active_tab', 'account');
session(['active_tab' => 'account']);


        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while updating the customer.']);

        }
    }
}
