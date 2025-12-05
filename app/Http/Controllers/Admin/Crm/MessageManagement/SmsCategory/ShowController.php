<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsCategory;

class ShowController extends Controller
{
    public function __invoke($unique_id)
    {
        $cat = SmsCategory::where('unique_id', $unique_id)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $cat
        ]);
    }
}
