<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\Customers\Customer;
use App\Helpers\MediaHelper;


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
        $mediaId = $customer->media;
        if ($mediaId) {
            MediaHelper::removeFile($customer->media);
        }
        $customer->update([
            'tax_document_media_id' => null,
            'tax_document_upload_date' => null,
        ]);

        flash('Tax document deleted successfully.')->success();

        return redirect()->route('admin.crm.customer_portal.edit', ['unique_id' => $customer->unique_id]);
    }
}
