<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Customers\Customer;


class TaxStatusUpdateController extends Controller
{
    /**
     * Handle AJAX status update (Approve/Reject).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required',
            'status' => 'required',
        ]);

        try {
            $customer = Customer::findOrFail($request->customer_id);

            $customer->update([
                'tax_document_status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ]);
        }
    }
}
