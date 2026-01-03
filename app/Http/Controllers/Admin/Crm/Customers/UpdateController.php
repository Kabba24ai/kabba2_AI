<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\UpdateRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use Illuminate\Support\Carbon;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request to update a customer.
     */
    public function __invoke(UpdateRequest $request, string $unique_id)
    {
        $validated = $request->validated();

        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        DB::beginTransaction();
        // dd($validated);
        try {
            $fullWebsite = trim(($validated['website_protocol'] ?? '') . ($validated['company_website'] ?? '') . ($validated['website_extension'] ?? ''));
           $customerData['company_website'] = $fullWebsite;

            $customerData = [
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
               'email' => $validated['email'] ?? null,
               
                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,
               
                'status' => $validated['status'] ?? 'Active',
                'is_guest' => $validated['is_guest'] ?? false,
                'tax_status' => $validated['tax_status'] ?? 'Taxable',
                //'tax_document_media_id' => $validated['tax_document_media_id'] ?? null,
               
                'is_credit_account' => $validated['is_credit_account'] ?? false,
                'credit_limit' => $validated['credit_limit'] ?? null,
               
                'tax_status' => $validated['tax_status'] ?? null,

                'tax_document_valid_until' => CustomHelper::parseDateFromInput($validated['tax_document_valid_until'] ?? null),
                'tax_document_upload_date' => CustomHelper::parseDateFromInput($validated['tax_document_upload_date'] ?? null),
                'account_application_completed' => CustomHelper::parseDateFromInput($validated['account_application_completed'] ?? null),

                'tax_document_status' => $validated['tax_document_review_status'] ?? '',

                'tax_status_approved_by' => $validated['tax_status_approved_by'] ?? 0,
                'account_approved_by' => $validated['account_approved_by'] ?? 0,

            ];



           //$customerData['company_website'] = ($validated['website_protocol'] ?? '') . ($validated['company_website'] ?? '') . ($validated['website_extension'] ?? '');
           $customerData['company_website'] = $fullWebsite;

            $customer->update($customerData);

            // Process address list
                if (!empty($validated['alladdresslist'])) {
                    $submittedAddresses = collect(json_decode($validated['alladdresslist'], true));

                    $existingIds = $submittedAddresses
                        ->filter(fn($addr) => !empty($addr['address_id']))
                        ->pluck('address_id')
                        ->toArray();

                    // Delete removed addresses
                    CustomerAddress::where('customer_id', $customer->id)
                        ->whereNotIn('id', $existingIds)
                        ->delete();

                    //  Loop through submitted and update/create
                    foreach ($submittedAddresses as $address) {
                        $data = [
                            'first_name'    => $address['first_name'] ?? null,
                            'last_name'     => $address['last_name'] ?? null,
                            'phone'         => $address['phone'] ?? null,
                            'type'          => $address['type'] ?? null,
                            'address'       => $address['address'] ?? null,
                            'city'          => $address['city'] ?? null,
                            'state_id'      => $address['state_id'] ?? null,
                            'zip_code'      => $address['zip_code'] ?? null,
                            'customer_id'   => $customer->id,
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
                }
                $customer->update($customerData);
            }

            DB::commit();

            flash('Customer updated successfully.')->success();

    session(['active_tab' => 'account']);


            return match ($request->input('action')) {
                'save' => redirect()->route('admin.crm.customers.edit', ['unique_id' => $customer->unique_id]),
                'save_new' => redirect()->route('admin.crm.customers.create'),
                default => redirect()->route('admin.crm.customers.index'),
            };
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the customer.'.$e)->error();
    session(['active_tab' => 'account']);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the customer.']);
        }
    }
}
