<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Requests
use App\Http\Requests\Admin\Crm\Customers\BulkDeleteRequest;
use App\Models\Customers\Customer;


class BulkDeleteController extends Controller
{
    /**
     * Handle bulk deletion of Customer.
     */
    public function __invoke(BulkDeleteRequest $request)
    {
        $uniqueIds = $request->validated()['unique_ids'] ?? [];

        if (empty($uniqueIds)) {
            return response()->json(['message' => 'No Customers selected for deletion.'], 422);
        }

        DB::transaction(function () use ($uniqueIds) {
            Customer::whereIn('unique_id', $uniqueIds)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Customers(s) deleted successfully.',
        ], 200);
    }
}
