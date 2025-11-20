<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\Customers\Customer;
use App\Helpers\MediaHelper;
use Illuminate\Support\Facades\Log;


class TaxDocumentDeleteController extends Controller
{
    /**
     * Handle the request to delete a product .
     *
     * @param  string  $unique_id
     * @return RedirectResponse
     */
    public function __invoke(string $unique_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        $mediaId = $customer->media;
        if ($mediaId) {
            MediaHelper::removeFile($customer->media);
        }

        $customer->update([
            'tax_document_media_id' => null,
            'tax_document_upload_date' => null,
            'tax_document_status' => '',
        ]);

        // if (request()->ajax()) {
            return response()->json(['success' => true]);
        // }

        // flash('Tax document deleted successfully.')->success();
        // return redirect()->back();
    }
}
