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

        if ($request->ajax()) {
            // $query = Customer::with('orders', 'addresses')
            //     ->whereIn('status', ['Active', 'Archived'])
            //     ->select('customers.*')->orderByRaw("
            //         CASE
            //             WHEN (is_credit_account = 1 AND credit_limit IS NOT NULL AND credit_limit != '') THEN 1  -- Good Standing
            //             ELSE 0  -- Bad Debt
            //         END DESC,  -- Bad Debt first
            //         CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
            //     ");


           $query = Customer::with('orders', 'addresses')
            ->whereIn('status', ['Active', 'Archived'])
            ->select('customers.*')
            // Bad Debt sort via the single canonical rule (Customer::badDebtSqlCondition):
            // outstanding_balance > 0 AND oldest_outstanding_age_days >= 60.
            // Credit-limit configuration is no longer part of Bad Debt.
            ->orderByRaw('CASE WHEN ' . Customer::badDebtSqlCondition() . ' THEN 1 ELSE 0 END ASC,
                CONCAT_WS(\' \', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
            ');


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

            /*
            |--------------------------------------------------------------------------
            | Tags Filter
            |--------------------------------------------------------------------------
            */

            if ($request->filled('tag')) {

                $query->where(function ($q) use ($request) {

                    $q->where('tags', 'LIKE', '%"'.$request->tag.'"%')
                    ->orWhere('tags', 'LIKE', '%'.$request->tag.'%');

                });
            }

            /*
            |--------------------------------------------------------------------------
            | Funnels Filter
            |--------------------------------------------------------------------------
            */

            if ($request->filled('funnel')) {

                $query->whereHas('funnels', function ($q) use ($request) {

                    $q->where(
                        'sales_funnels.id',
                        $request->funnel
                    );

                });
            }

            $perPage = $request->input('per_page', 30);
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
