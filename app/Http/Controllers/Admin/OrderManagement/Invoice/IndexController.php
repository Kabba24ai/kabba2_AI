<?php

namespace App\Http\Controllers\Admin\OrderManagement\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    
    public function __invoke(Request $request)
    {
        
        return view('admin.order_management.invoice.index');
    }
}
