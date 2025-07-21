<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

// Models


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */ 
    public function __invoke(Request $request)
    {
        $query = Customer::with('orders','addresses')->whereIn('status', ['Active', 'Inactive']);
    
        if ($request->filled('search_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search_name . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search_name . '%');
            });
        }

        
        if ($request->filled('search_company_name')) {
            $query->where('company_name', 'like', '%' . $request->search_company_name . '%');
        }
        
    
        if ($request->filled('search_phone')) {
            $searchPhone = CustomHelper::unformatPhone($request->search_phone);
            $query->where('phone', 'like', '%' . $searchPhone . '%');
        }
    

        if ($request->filled('tax_status') && $request->tax_status !== 'All') {
            $query->where('tax_status', $request->tax_status);
        }

       

        $customers = $query->latest()->paginate(10)->withQueryString();

        
        if ($request->ajax()) {
    $tableView = view('admin.crm.customers.partials._table', compact('customers'))->render();

    return response()->json([
        'html' => $tableView,
        'total' => $customers->total(),
    ]);
}
    
        return view('admin.crm.customers.index', compact('customers'));
    }
    
}
