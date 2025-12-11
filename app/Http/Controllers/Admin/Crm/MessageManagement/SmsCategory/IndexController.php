<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customers\SmsCategory;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsCategory\StoreRequest;

class IndexController extends Controller
{
   public function __invoke()
    {
        $cat = SmsCategory::orderBy('name', 'ASC')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $cat
        ]);
    }
}
