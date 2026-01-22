<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;

// Models

class FetchCustomerTags extends Controller
{
    public function __invoke($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        return response()->json([
            'success' => true,
            'tags' => $customer->tag_objects,
        ]);
    }
}
