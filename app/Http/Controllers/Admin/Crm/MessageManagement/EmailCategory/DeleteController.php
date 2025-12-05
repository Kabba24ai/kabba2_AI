<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory;

use App\Http\Controllers\Controller;
use App\Models\Customers\EmailCategory;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        $cat = EmailCategory::where('unique_id', $unique_id)->firstOrFail();
        $cat->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully.'
        ]);
    }
}
