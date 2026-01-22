<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Customers\Customer;
use App\Helpers\MediaHelper;
use App\Helpers\CustomHelper;


class TaxDocumentUploadController extends Controller
{
    /**
     * Handle AJAX tax document upload.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_document' => 'required',
            'tax_document_type' => 'required',
            'customer_id' => 'required'
        ]);

        try {
            $customer = Customer::with('media')->findOrFail($validated['customer_id']);

            // Remove previous media if it exists
            if (!is_null($customer->media)) {
                MediaHelper::removeFile($customer->media);
            }

            // Upload new document
            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $validated['tax_document'],
                'customers',
                $customer
            );

            if (!empty($mediaData['mediaObj'])) {
                $customer->update([
                    'tax_document_media_id'    => $mediaData['mediaObj']->id,
                    'tax_document_type'        => $validated['tax_document_type'],
                    'tax_document_upload_date' => now(),
                    'tax_document_status' => '' , 
                ]);

                $customer->load('media');
            }

           return response()->json([
                'success'        => true,
                'html'           => view('admin.crm.customers.partials.tax_doc_preview', ['customer' => $customer])->render(),
                'message'        => 'Document uploaded successfully',
                'upload_date' => CustomHelper::formatDate($customer->tax_document_upload_date),
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage(),
            ]);
        }
    }
}
