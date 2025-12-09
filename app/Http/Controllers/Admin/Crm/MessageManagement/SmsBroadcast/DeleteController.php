<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;

class DeleteController extends Controller
{
    public function __invoke($id)
    {
        $cat = SmsBroadcast::where('id', $id)->firstOrFail();
        $cat->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'SmsBroadcast deleted successfully.'
        ]);
    }
}
