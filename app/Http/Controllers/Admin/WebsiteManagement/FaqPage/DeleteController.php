<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\WebsiteManagement\FaqPage\FaqCategory;


class DeleteController extends Controller
{
    /**
     * Handle the request to delete a product .
     *
     * @param  string  $unique_id
     * @return RedirectResponse
     */
    public function __invoke(string $unique_id): RedirectResponse
    {


        $objCustomer = FaqCategory::where('unique_id', $unique_id)->firstOrFail();
		$objCustomer->questions()->delete();
		$objCustomer->delete();

        flash('FAQ category deleted successfully.')->success();

        return redirect()->route('admin.website-management.faq-page.index');

    }
}
