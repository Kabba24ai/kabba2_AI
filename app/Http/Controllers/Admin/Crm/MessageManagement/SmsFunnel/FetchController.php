<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsCategory;
use Exception;

// Models
use App\Models\Customers\SmsFunnel;

class FetchController extends Controller
{
    public function __invoke($categoryId)
    {
        try {
            $funnels = SmsFunnel::where('sms_cat_id', $categoryId)->orderBy('name', 'ASC')->get();

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Sms messages not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'funnels' => $funnels,
        ]);
    }
}
