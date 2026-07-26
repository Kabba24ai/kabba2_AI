<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Helpers\MediaHelper;
use App\Helpers\CustomHelper;


class TaxDocumentUploadController extends Controller
{
    /**
     * Handle AJAX tax document upload for the AUTHENTICATED customer.
     *
     * Security: the document is always attached to Auth::guard('customer')
     * ->user(); a request-supplied customer_id is ignored. This prevents a
     * customer from overwriting/destroying another customer's tax document
     * (which deletes the prior media file) by posting a different customer_id.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_document' => 'required',
            'tax_document_type' => 'required',
        ]);

        try {
            $customer = Auth::guard('customer')->user()->load('media');

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
                'html'           => view('front.customer.dashboard.partials.tax_doc_preview', ['customer' => $customer])->render(),
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
