<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerNote;

use App\Models\Customers\CustomerAddress;

// Request
use App\Http\Requests\Admin\Crm\Customers\StoreRequest;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

use App\Helpers\CustomHelper;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {

    dd($request->all());
    die();
    
        $validated = $request->validated();

        // Auto-copy billing address to shipping if sameAsBilling checked
        if ($request->boolean('sameAsBilling') && isset($request->addresses[0])) {
            $addresses = $request->addresses;
            $billing   = $addresses[0];

            // If shipping address (index 1) exists, fill it; otherwise, append a new one
            if (isset($addresses[1]) && strtolower($addresses[1]['type'] ?? '') === 'shipping') {
                $addresses[1] = array_merge($addresses[1], [
                    'type'        => 'Shipping',
                    'is_primary'  => $billing['is_primary'] ?? 0,
                    'first_name'  => $billing['first_name'] ?? null,
                    'last_name'   => $billing['last_name'] ?? null,
                    'address'     => $billing['address'] ?? null,
                    'city'        => $billing['city'] ?? null,
                    'state_id'    => $billing['state_id'] ?? null,
                    'zip_code'    => $billing['zip_code'] ?? null,
                    'Country'     => $billing['Country'] ?? null,
                    'phone'       => $billing['phone'] ?? null,
                ]);
            } else {
                // If not found, add a new one
                $addresses[] = [
                    'type'        => 'Shipping',
                    'is_primary'  => $billing['is_primary'] ?? 0,
                    'first_name'  => $billing['first_name'] ?? null,
                    'last_name'   => $billing['last_name'] ?? null,
                    'address'     => $billing['address'] ?? null,
                    'city'        => $billing['city'] ?? null,
                    'state_id'    => $billing['state_id'] ?? null,
                    'zip_code'    => $billing['zip_code'] ?? null,
                    'Country'     => $billing['Country'] ?? null,
                    'phone'       => $billing['phone'] ?? null,
                ];
            }

            // Update request
            $request->merge(['addresses' => $addresses]);
        }

        // dd($request->addresses);

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
                'phone' => isset($validated['phone']) ? CustomHelper::unformatPhone($validated['phone']) : null,
                'company_phone' => isset($validated['company_phone']) ? CustomHelper::unformatPhone($validated['company_phone']) : null,

                'dob' => $validated['dob'] ?? null,
                'status' => $validated['status'] ?? 'Active',

                'tax_document_status' => $validated['tax_document_review_status'] ?? '',

                'is_guest' => $validated['is_guest'] ?? false,
                'tax_status' => $validated['tax_status'] ?? 'Taxable',
                'tax_document_media_id' => $validated['tax_document_media_id'] ?? null,

                'tax_document_upload_date' => CustomHelper::parseDateFromInput($validated['tax_document_upload_date'] ?? null),
                'tax_document_valid_until' => CustomHelper::parseDateFromInput($validated['tax_document_valid_until'] ?? null),
                'account_application_completed' => CustomHelper::parseDateFromInput($validated['account_application_completed'] ?? null),

                'tax_status_approved_by' => $validated['tax_status_approved_by'] ?? null,
                'account_approved_by' => $validated['account_approved_by'] ?? null,
                'is_credit_account' => $validated['is_credit_account'] ?? false,
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

            // Create the customer
            $customer = Customer::create($customerData);

            if ($request->has('addresses') && is_array($request->addresses)) {
                foreach ($request->addresses as $address) {
                    $customer->addresses()->create([
                        'first_name'   => $address['first_name'] ?? null,
                        'last_name'    => $address['last_name'] ?? null,
                        'phone'        => $address['phone'] ?? null,
                        'type'         => $address['type'] ?? null,
                        'city'         => $address['city'] ?? null,
                        'state_id'     => $address['state_id'] ?? null,
                        'zip_code'     => $address['zip_code'] ?? null,
                        'address'      => $address['address'] ?? null,
                        'is_primary'   => $address['is_primary'] ?? 0,
                        'country'     => $address['Country'] ?? null,
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

            //  Handle customer notes (from notes_json)
            if (!empty($validated['notes_json'])) {
                $notes = json_decode($validated['notes_json'], true);

                if (is_array($notes)) {
                    foreach ($notes as $note) {
                        $customer->notes()->create([
                            'description'   => $note['text'] ?? '',
                            'created_by'    => $note['userId'] ?? null,
                            'created_date'  => now()->toDateString(),
                            'created_time'  => now()->format('H:i:s'),
                        ]);
                    }
                }
            }

            DB::commit();

            flash('Customer created successfully.')->success();

            return match ($request->input('action')) {
                'save' => redirect()->route('admin.crm.customers.index'),
                'save_new' => redirect()->route('admin.crm.customers.create'),
                default => redirect()->route('admin.crm.customers.index'),
            };
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the customer.' . $e)->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the customer.']);
        }
    }
}
