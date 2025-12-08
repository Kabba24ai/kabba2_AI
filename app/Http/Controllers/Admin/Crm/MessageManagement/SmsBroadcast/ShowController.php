<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsCategory;
use Illuminate\Support\Facades\Log; // Import the Log facade

class ShowController extends Controller
{
    public function __invoke($id)
    {
        try {
            // Log the incoming ID
            // Log::info("ShowController called for broadcast ID: {$id}");

            $broadcast = SmsBroadcast::where('id', $id)->firstOrFail();

            // Log the broadcast fetched
            // Log::info('Fetched broadcast:', [
            //     'id' => $broadcast->id,
            //     'name' => $broadcast->name,
            //     'category_id' => $broadcast->sms_cat_id
            // ]);

            $categories = SmsCategory::all(['id', 'name']) ;

            // Optionally log category count
            // Log::info('Fetched categories count: ' . $categories->count());

            return response()->json([
                'status' => 'success',
                'broadcast' => [
                    'id' => $broadcast->id,
                    'name' => $broadcast->name,
                    'description' => $broadcast->description,
                    'category_id' => $broadcast->sms_cat_id,
                ],
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            // Log any exception
            Log::error("ShowController error: " . $e->getMessage(), ['id' => $id]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch SMS broadcast.'
            ], 500);
        }
    }
}
