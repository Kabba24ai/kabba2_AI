<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Customers\Customer;



class CustomerStatusUpdateController extends Controller
{
    /**
     * Handle the request to delete a product .
     *
     * @param  string  $unique_id
     * @return RedirectResponse
     */
    public function __invoke(string $unique_id, Request $request): RedirectResponse
    {
        $customer_data = Customer::where('unique_id', $unique_id)->firstOrFail();

        try {
            $customer = Customer::findOrFail($customer_data->id);

            $customer->update([
                'status' => $request->cstatus,
            ]);

            flash('Status updated successfully.')->success();
        session()->flash('active_tab', 'account');
            return redirect()->back();

        } catch (\Throwable $e) {
            report($e);

            flash('Status updation failed.')->error();
        session()->flash('active_tab', 'account');
            return redirect()->back();
        }
    }
}
