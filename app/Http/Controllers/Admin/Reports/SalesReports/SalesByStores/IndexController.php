<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\SalesByStores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.reports.sales_reports.sales_by_stores.index');
    }
}
