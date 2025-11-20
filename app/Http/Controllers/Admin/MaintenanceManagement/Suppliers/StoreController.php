<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\StoreRequest;
use App\Models\MaintenanceManagement\Supplier;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        // dd($validated);

        DB::beginTransaction();

        try {
            // Create Supplier
            $supplier = Supplier::create([
                'name'                   => $validated['supplierCompany'],
                'email'                  => $validated['supplierEmail'] ?? null,
                'phone'                  => $validated['supplierPhone'] ?? null,
                'website'                => $validated['supplierWebsite'] ?? null,
                'address'                => $validated['supplierAddress'] ?? null,
                'city'                   => $validated['supplierCity'] ?? null,
                'state_id'               => $validated['supplierState'] ?? null,
                'zip_code'               => $validated['supplierZip'] ?? null,
                'country'                => $validated['supplierCountry'] ?? null,
                'tax_id'                 => $validated['supplierTax'] ?? null,
                'supplier_category_id'   => $validated['supplierCategory'] ?? null,
                'status'                 => $validated['supplierStatus'],
                'payment_terms'          => $validated['supplierPaymentTerms'],
                'tags'                   => isset($validated['tags']) ? implode(',', $validated['tags']) : null,
                'primary_contact_name'   => $validated['primaryContactName'] ?? null,
                'primary_contact_email'  => $validated['primaryContactEmail'] ?? null,
                'primary_contact_phone'  => $validated['primaryContactPhone'] ?? null,
                'inside_sales_name' => $validated['insideSalesName'] ?? null,
                'inside_sales_email' => $validated['insideSalesEmail'] ?? null,
                'inside_sales_phone' => $validated['insideSalesPhone'] ?? null,
                'technical_support_name' => $validated['technicalSupportName'] ?? null,
                'technical_support_email' => $validated['technicalSupportEmail'] ?? null,
                'technical_support_phone' => $validated['technicalSupportPhone'] ?? null,
            ]);

            // Handle company logo upload
            if ($request->hasFile('upload_company_logo')) {
                $mediaData = MediaHelper::uploadStorageFile(
                    'Public Asset',
                    $request->file('upload_company_logo'),
                    'suppliers',
                    $supplier
                );

                if (!empty($mediaData['mediaObj'])) {
                    $supplier->update([
                        'company_logo_media_id' => $mediaData['mediaObj']->id
                    ]);
                }
            }

            flash('Supplier created successfully.')->success();

            DB::commit();

            return redirect()->route('admin.maintenance-management.suppliers.index') ;

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the supplier.'
            ], 500);
        }
    }
}
