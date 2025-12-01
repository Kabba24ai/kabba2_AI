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
        //     $query = Customer::with('orders','addresses')->whereIn('status', ['Active', 'Archived'])->orderByRaw("
        // CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
        // ");


        if ($request->ajax()) {
            $query = Customer::with('orders', 'addresses')
                ->whereIn('status', ['Active', 'Archived'])
                ->select('customers.*')->orderByRaw("
                    CASE
                        WHEN (is_credit_account = 1 AND credit_limit IS NOT NULL AND credit_limit != '') THEN 1  -- Good Standing
                        ELSE 0  -- Bad Debt
                    END ASC,  -- Bad Debt first
                    CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
                ");

            if ($request->filled('search_name')) {
                $query->where(function ($q) use ($request) {
                    $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
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

            $perPage = $request->input('per_page', 10);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $customers = $query->latest('id')->paginate($perPageVal)->withQueryString();

            $tableView = view('admin.crm.customers.partials._table', compact('customers'))->render();

            return response()->json([
                'html' => $tableView,
                'total' => $customers->total(),
            ]);
        }

        return view('admin.crm.customers.index');
    }
}
