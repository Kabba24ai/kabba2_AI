<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;

class DeleteController extends Controller
{
    public function __invoke($id)
    {
        $funnel = SmsFunnel::findOrFail($id);
        $funnel->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'SMS Funnel deleted successfully!'
        ]);
    }
}
