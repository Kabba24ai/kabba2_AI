<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsCategory;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        $cat = SmsCategory::where('unique_id', $unique_id)->firstOrFail();
        $cat->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully.'
        ]);
    }
}
