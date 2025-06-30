<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;

// Request
use App\Http\Requests\Admin\CRM\CustomerPortal\StoreRequest;

// Models


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

            $customerData = [
                'unique_id' => $uniqueId,
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'company_phone' => $validated['company_phone'] ?? null, // Added company_phone
                'dob' => $validated['dob'] ?? null,
                'status' => $validated['status'] ?? 'Active',
                'is_guest' => $validated['is_guest'] ?? false,
                'tax_status' => $validated['tax_status'] ?? 'Taxable',
                'tax_document_media_id' => $validated['tax_document_media_id'] ?? null,
                'tax_document_upload_date' => $validated['tax_document_upload_date'] ?? null,
                'tax_document_valid_until' => $validated['tax_document_valid_until'] ?? null,
                'is_credit_account' => $validated['is_credit_account'] ?? false,
                'credit_limit' => $validated['credit_limit'] ?? null,
                'account_application_completed' => $validated['applicationcom'] ?? null,
                'tax_status' => $validated['tax_status'] ?? null,
                'tax_document_upload_date' => $validated['tax_document_upload_date'] ?? null,
            ];

            // Create the customer
            $customer = Customer::create($customerData);

            // Handle address creation if address fields are present
            if (isset($validated['address_line_1']) || isset($validated['city']) || isset($validated['state'])) {
                $addressData = [
                    'customer_id' => $customer->id,
                    'address_line_1' => $validated['address_line_1'] ?? null,
                    'address_line_2' => $validated['address_line_2'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'country' => $validated['country'] ?? 'US',
                    'website' => $validated['website'] ?? null,
                    'is_primary' => true, // First address is primary
                ];

                // Create address (assuming you have an Address model)
                $customer->addresses()->create($addressData);
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
                'save' => redirect()->route('admin.crm.customer_portal.edit', ['unique_id' => $customer->unique_id]),
                'save_new' => redirect()->route('admin.crm.customer_portal.create'),
                default => redirect()->route('admin.crm.customer_portal.index'),
            };

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the customer.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the customer.']);
        }
    }
}
