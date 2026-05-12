<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;

class CreateController extends Controller
{
    public function __invoke()
    {
        return view('admin.crm.kabba_ai_customers.create');
    }
}
