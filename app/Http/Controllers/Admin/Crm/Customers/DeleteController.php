<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\Customers\Customer;


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
        $objCustomer = Customer::where('unique_id', $unique_id)->firstOrFail();

  

         // Update status instead of deleting
        $objCustomer->status = 'Inactive';
        $objCustomer->save();

        flash('Customer deleted successfully.')->success();

        return redirect()->route('admin.crm.customers.index');
    }
}
