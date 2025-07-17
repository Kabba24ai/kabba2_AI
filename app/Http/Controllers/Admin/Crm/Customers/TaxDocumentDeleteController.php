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
    public function __invoke(string $unique_id): RedirectResponse
    {
        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        Log::info('Customer media info', ['media' => $customer->media]);

        $mediaId = $customer->media;
        if ($mediaId) {
            MediaHelper::removeFile($customer->media);
        }
        $customer->update([
            'tax_document_media_id' => null,
            'tax_document_upload_date' => null,
            'tax_document_status' =>  '',

        ]);

        flash('Tax document deleted successfully.')->success();

        return redirect()->back();
    }
}
