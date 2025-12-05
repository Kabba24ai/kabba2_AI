<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class IndexController extends Controller
{
    
    public function __invoke(Request $request)
    {
        return view('admin.crm.message_management.index');
    }
}
