<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Services\AuthorizeNetService;
use Illuminate\Http\Request;

class TransactionDetailsController extends Controller
{
    /**
     * Get transaction details from Authorize.Net.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($transactionId, Request $request)
    {
        try {

            if ($transactionId === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'transaction_id is required',
                    'data' => [],
                ], 422);
            }

            $authorizeNetService = new AuthorizeNetService();
            $result = $authorizeNetService->getTransactionDetailsSummary($transactionId);

            if ($result['status'] !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to fetch transaction details',
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction details retrieved successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction details: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
