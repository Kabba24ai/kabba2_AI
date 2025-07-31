<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Customer\Dashboard\UpdateRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use Illuminate\Support\Carbon;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request to update a customer.
     */
    public function __invoke(UpdateRequest $request)
    {

        $validated = $request->validated();

        $customer = Customer::where('unique_id', $validated['unique_id'])->firstOrFail();

        DB::beginTransaction();
        
        try {

            $customerData = [
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,
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
                            'address'       => $address['address'] ?? null,
                            'city'          => $address['city'] ?? null,
                            'state_id'      => $address['state_id'] ?? null,
                            'zip_code'      => $address['zip_code'] ?? null,
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
           
            if ($request->hasFile('tax_document')) {
                if ($request->has('tax_document') && !is_null($request->file('tax_document'))) {
                    if (!is_null($customer->media)) {
                        MediaHelper::removeFile($customer->media);
                    }
                }

                $mediaData = MediaHelper::uploadStorageFile(
                    'Public Asset',
                    $request->file('tax_document'),
                    'customers',
                    $customer
                );

                if (!empty($mediaData['mediaObj'])) {
                    $customerData['tax_document_media_id'] = $mediaData['mediaObj']->id;
                    
                    $customerData['tax_document_upload_date'] = now(); // or keep original if needed

                    $customerData['tax_document_status'] = '';

                }
                $customer->update($customerData);
            }

            DB::commit();

            flash('Customer updated successfully.')->success();
            session()->flash('active_tab', 'account');

            return redirect()
           ->back() ;


        } catch (\Throwable $e) {
           DB::rollBack();
        report($e);

        flash('Something went wrong while updating the customer.'.$e)->error();
        session()->flash('active_tab', 'account');

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while updating the customer.']);

        }
    }
}
