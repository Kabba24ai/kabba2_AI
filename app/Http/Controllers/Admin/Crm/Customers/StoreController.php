<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;

// Request
use App\Http\Requests\Admin\CRM\Customers\StoreRequest;
use Illuminate\Support\Carbon;
use App\Helpers\CustomHelper;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
       $validated = $request->validated();

        DB::beginTransaction();
        
        try {
            // Generate unique_id for new customer
            $uniqueId = Str::uuid()->toString(); // or your custom unique ID generation logic

            $fullWebsite = ($validated['website_protocol'] ?? '') . ($validated['company_website'] ?? '') . ($validated['website_extension'] ?? '');
            
            $customerData = [
                'unique_id' => $uniqueId,
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,
                'company_website' => $validated['company_website'] ?? null, 
                'dob' => $validated['dob'] ?? null,
                'status' => $validated['status'] ?? 'Active',
                
                'tax_document_status' => $validated['tax_document_review_status'] ?? '',

                'is_guest' => $validated['is_guest'] ?? false,
                'tax_status' => $validated['tax_status'] ?? 'Taxable',
                'tax_document_media_id' => $validated['tax_document_media_id'] ?? null,
                'tax_document_upload_date' => !empty($validated['tax_document_upload_date'])
                    ? Carbon::createFromFormat('m/d/Y', $validated['tax_document_upload_date'])->format('Y-m-d')
                    : null,
                'tax_document_valid_until' => !empty($validated['tax_document_valid_until'])
                    ? Carbon::createFromFormat('m/d/Y', $validated['tax_document_valid_until'])->format('Y-m-d')
                    : null,
                    'tax_status_approved_by' => $validated['tax_status_approved_by'] ?? null,
                    'account_approved_by' => $validated['account_approved_by'] ?? null,
                'is_credit_account' => $validated['is_credit_account'] ?? false,
                'credit_limit' => $validated['credit_limit'] ?? null,
                'account_application_completed' => !empty($validated['account_application_completed'])
                ? Carbon::createFromFormat('m/d/Y', $validated['account_application_completed'])->format('Y-m-d')
                : null,
                'tax_status' => $validated['tax_status'] ?? null,
            ];

            $customerData['company_website'] = $fullWebsite;
            // Create the customer
            $customer = Customer::create($customerData);

            if (!empty($validated['alladdresslist'])) {
                $addresses = json_decode($validated['alladdresslist'], true); 
            
                

                foreach ($addresses as $address) {
                    $customer->addresses()->create([
                        'first_name'    => $address['first_name'] ?? null,
                        'last_name'     => $address['last_name'] ?? null,
                        'phone'         => $address['phone'] ?? null,
                        'type'          => $address['type'] ?? null,
                        'city'          => $address['city'] ?? null,
                        'state_id'      => $address['state_id'] ?? null,
                        'zip_code'      => $address['zip_code'] ?? null,
                        'address'      => $address['address'] ?? null,
                        'customer_id'   => $customer->id,
                    ]);
                }
            }
            

            // Handle tax document upload
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

            flash('Customer created successfully.')->success();

            return match ($request->input('action')) {
                'save' => redirect()->route('admin.crm.customers.edit', ['unique_id' => $customer->unique_id]),
                'save_new' => redirect()->route('admin.crm.customers.create'),
                default => redirect()->route('admin.crm.customers.index'),
            };

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the customer.'.$e)->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the customer.']);
        }
    }
}
