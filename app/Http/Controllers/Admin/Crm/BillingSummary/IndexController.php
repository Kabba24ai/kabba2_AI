<?php

namespace App\Http\Controllers\Admin\Crm\BillingSummary;

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
        $query = Customer::with('orders.payments','addresses','accounts')->whereIn('status', ['Active', 'Inactive']);
    
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
        
        
        if ($request->filled('alert_status') && $request->alert_status === 'warning') {
            $query->whereHas('accounts', function ($q) {
                $q->where('type', 'payment');
            });
        }


        if ($request->filled('credit_types')) {
            $types = $request->input('credit_types');

            $query->where(function ($q) use ($types) {
                if (in_array('approved', $types)) {
                    $q->orWhere('is_credit_account', '1'); // Change to your actual column name
                }
                if (in_array('none', $types)) {
                 $q->orWhereNull('credit_limit');
                }
            });
        }


         $customers = $query->latest()->paginate(10)->withQueryString();
        
        if ($request->ajax()) {
            
            $tableView = view('admin.crm.customers.partials._table_billingsummary', compact('customers'))->render();

            return response()->json([
                'html' => $tableView,
                'total' => $customers->total(),
            ]);

        }
    
    }
    
}
