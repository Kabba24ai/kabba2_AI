<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customers\EmailCategory;
use App\Http\Requests\Admin\Crm\MessageManagement\EmailCategory\StoreRequest;

class IndexController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'status' => 'success',
            'data' => EmailCategory::orderBy('id', 'DESC')->get(),
        ]);
    }
}
