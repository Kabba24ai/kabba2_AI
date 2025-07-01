<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CRM\CustomerPortal\UpdateRequest;
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
            $customerData = [
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
               
                'company_website' => $validated['company_website'] ?? null, // Added company_website
                'email' => $validated['email'] ?? null,
               
                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,
               
                'status' => $validated['status'] ?? 'Active',
                'is_guest' => $validated['is_guest'] ?? false,
                'tax_status' => $validated['tax_status'] ?? 'Taxable',
                'tax_document_media_id' => $validated['tax_document_media_id'] ?? null,
               
              
                'is_credit_account' => $validated['is_credit_account'] ?? false,
                'credit_limit' => $validated['credit_limit'] ?? null,
               
                'tax_status' => $validated['tax_status'] ?? null,
               'tax_document_upload_date' => !empty($validated['tax_document_upload_date'])
    ? Carbon::parse($validated['tax_document_upload_date'])->format('Y-m-d')
    : null,

'tax_document_valid_until' => !empty($validated['tax_document_valid_until'])
    ? Carbon::parse($validated['tax_document_valid_until'])->format('Y-m-d')
    : null,

'account_application_completed' => !empty($validated['account_application_completed'])
    ? Carbon::parse($validated['account_application_completed'])->format('Y-m-d')
    : null,

                'tax_status_approved_by' => $validated['tax_status_approved_by'] ?? null,
                'account_approved_by' => $validated['account_approved_by'] ?? null,


            ];

            $customer->update($customerData);

            // Check if any relevant fields are filled (you can modify this list)
            $addressInput = [
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
            ];
            $hasCompanyFields = collect($addressInput)->only([
                'first_name',
                'last_name',
                'email',
                'billing_address',
                'phone',
                'website',
            ])->filter()->isNotEmpty();

            if ($hasCompanyFields) {
                // Get or create address record
                $customerAddress = CustomerAddress::firstOrNew(['customer_id' => $customer->id]);

                $customerAddress->fill([
                    'address' => $addressInput['billing_address'] ?? $customerAddress->billing_address,
                    'first_name' => $addressInput['first_name'] ?? $customerAddress->first_name,
                    'last_name' => $addressInput['last_name'] ?? $customerAddress->last_name,
                    'phone' => $addressInput['phone'] ?? $customerAddress->phone,
                    'email' => $addressInput['email'] ?? $customerAddress->email,
                    'website' => $addressInput['website'] ?? $customerAddress->website,
                    
                ]);

                $customerAddress->customer_id = $customer->id;
                $customerAddress->save();
            }

           
            if ($request->hasFile('tax_document')) {
            
                $mediaData = MediaHelper::uploadStorageFile(
                    'Public Asset', 
                    $request->file('tax_document'), 
                    'customers', 
                    $customer
                );
            
               
                if (!empty($mediaData['mediaObj'])) {
                    $customer->update([
                        'tax_document_media_id' => $mediaData['mediaObj']->id,
                        'tax_document_upload_date' => now(),
                    ]);
                }
            }

            DB::commit();

            flash('Customer updated successfully.')->success();

            return match ($request->input('action')) {
                'save' => redirect()->route('admin.crm.customer_portal.edit', ['unique_id' => $customer->unique_id]),
                'save_new' => redirect()->route('admin.crm.customer_portal.create'),
                default => redirect()->route('admin.crm.customer_portal.index'),
            };
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the customer.'.$e)->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the customer.']);
        }
    }
}
