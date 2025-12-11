<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use App\Models\Customers\SmsCategory;
use Illuminate\Support\Facades\Log;
use Exception;

class ShowController extends Controller
{
    public function __invoke($id)
    {
        try {
            $funnel = SmsFunnel::findOrFail($id);
            $categories = SmsCategory::select('id', 'name')->get();

            return response()->json([
                'status' => 'success',
                'funnel' => $funnel,
                'categories' => $categories
            ], 200);

        } catch (Exception $e) {

            Log::error("SMS Funnel Show Error", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load SMS Funnel.'
            ], 500);
        }
    }
}
