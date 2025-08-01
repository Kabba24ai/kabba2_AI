<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\TermsAndConditions\PostRequest;

// Enums
use App\Enums\Orders\OrderTermsStatus;
use App\Helpers\TermsContentHelper;
// Models
use App\Models\Orders\Order;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($orderUniqueId, PostRequest $request)
    {
        // Validate the request
        $validatedData = $request->validated();

        try {
            $signature = $validatedData['signature'] ?? '';
            $order = Order::query()->with('products')->where('unique_id', $orderUniqueId)->firstOrFail();

            $termsArr = TermsContentHelper::generateAcceptedTermsContentFromArray($order->terms_collection, $order->customer_name ?? 'Customer', $signature);

            // Update order terms status
            $order->terms_status = OrderTermsStatus::Accepted;
            $order->signature_image = $signature ?? '';
            $order->accepted_terms_content = $termsArr['accepted_terms_content'] ?? '';
            $order->terms_accepted_at = now();
            $order->save();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Terms and conditions signed successfully.']);
    }
}
