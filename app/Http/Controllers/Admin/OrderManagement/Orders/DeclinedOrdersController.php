<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Services\AuthorizeNetService;
use Illuminate\Http\Request;

class DeclinedOrdersController extends Controller
{
    /**
     * Get declined orders from Authorize.Net
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            // Get days_back parameter (null = get all available, default 365 if specified)
            $daysBack = $request->input('days_back');

            if ($daysBack === null) {
                // Get all available transactions (maximum Authorize.Net allows)
                $daysBack = 365; // Authorize.Net typically allows max 1 year of history
                $searchMessage = "all available";
            } else {
                $daysBack = (int) $daysBack;
                // Limit to max 365 days
                if ($daysBack > 365) {
                    $daysBack = 365;
                }
                $searchMessage = "last {$daysBack} days";
            }

            $authorizeNetService = new AuthorizeNetService();
            $result = $authorizeNetService->getDeclinedTransactions($daysBack);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [],
                ]);
            }

            // Log for debugging
            logger()->info("Declined orders retrieved: " . count($result['transactions']) . " transactions for {$daysBack} days");

            return response()->json([
                'success' => true,
                'message' => $result['message'] . " (searched {$searchMessage})",
                'data' => $result['transactions'],
                'total_count' => $result['total_count'],
                'days_searched' => $daysBack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching declined orders: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
