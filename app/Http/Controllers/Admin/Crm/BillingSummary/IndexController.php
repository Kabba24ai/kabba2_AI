<?php

namespace App\Http\Controllers\Admin\Crm\BillingSummary;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Models

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            $query = Customer::with('orders.payments', 'addresses', 'accounts')->whereIn('status', ['Active', 'Archived']);

            // Apply filters
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

            if ($request->filled('alert_status')) {
                switch ($request->alert_status) {
                    case 'warning':
                        $query->whereHas('accounts', function ($q) {
                            $q->where('type', 'payment');
                        });
                        break;
                    case 'with_balance':
                        $query->where('available_credit_balance', '>', 0);
                        break;
                    case 'active':
                        $query->where('status', 'Active');
                        break;
                    case 'inactive':
                        $query->where('status', 'Archived');
                        break;
                }
            }

            if ($request->filled('credit_types')) {
                $types = $request->input('credit_types');

                $query->where(function ($q) use ($types) {
                    if (in_array('approved', $types)) {
                        $q->orWhere('is_credit_account', '1');
                    }
                    if (in_array('none', $types)) {
                        $q->orWhereNull('credit_limit');
                    }
                });
            }

            // Apply sorting
            if ($request->filled('sort')) {
                switch ($request->sort) {
                    case 'balance':
                        $query->orderByRaw('ROUND(available_credit_balance, 2) DESC')->orderByRaw("
        CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
    ");
                        break;
                    case 'days':
                        $query
                            ->joinSub(DB::table('customer_accounts')->select('customer_id', DB::raw('MAX(date) as last_payment_date'))->where('type', 'payment')->groupBy('customer_id'), 'last_payment', function ($join) {
                                $join->on('last_payment.customer_id', '=', 'customers.id');
                            })
                            ->select('customers.*')
                            ->selectRaw('DATEDIFF(NOW(), last_payment.last_payment_date) as days_since_last_payment_sql')
                            ->orderBy('days_since_last_payment_sql', 'desc')->orderByRaw("
        CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
    ");
                        break;
                    case 'bad_debt':
                        $query
                            ->leftJoinSub(DB::table('customer_accounts')->select('customer_id', DB::raw('MAX(date) as last_payment_date'))->where('type', 'payment')->groupBy('customer_id'), 'last_payment', function ($join) {
                                $join->on('last_payment.customer_id', '=', 'customers.id');
                            })
                            ->select('customers.*')
                            ->selectRaw('DATEDIFF(NOW(), last_payment.last_payment_date) as days_since_last_payment_sql')
                            ->where(function ($q) {
                                $q->whereRaw('DATEDIFF(NOW(), last_payment.last_payment_date) > 45')->orWhere(function ($q2) {
                                    $q2->where(function ($q3) {
                                        $q3->where('credit_limit', '<=', 0)->orWhereNull('credit_limit')->orWhere('is_credit_account', '=', 0);
                                    })->where('available_credit_balance', '>', 0);
                                });
                            })
                            ->orderByRaw('ROUND(available_credit_balance, 2) DESC')->orderByRaw("
        CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
    ");

                        break;
                }
            } else {
                // Default sorting by available_credit_balance
                $query->orderByRaw('ROUND(available_credit_balance, 2) DESC')->orderByRaw("
        CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
    ");
            }
            // Handle AJAX request for filtering and sorting
            $perPage = $request->input('per_page', 10);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

            // Clone AFTER filters are applied this will send whne ajex request
            $baseQuery = clone $query;
            $allCustomers = $baseQuery->get();

            $totalOutstanding = $allCustomers->sum('available_credit_balance');

            $overdueCustomers = $allCustomers->filter(function ($customer) {
                return $customer->credit_limit !== null && $customer->available_credit_balance > $customer->credit_limit;
            });

            $overdueCustomerCount = $overdueCustomers->count();

            $totalOverdueAmount = $overdueCustomers->sum(function ($customer) {
                return abs($customer->credit_limit - $customer->available_credit_balance);
            });


            $customers = $query->paginate($perPageVal)->withQueryString();

            $tableView = view('admin.crm.billing_summary.partials._table', compact('customers'))->render();

            return response()->json([
                'html' => $tableView,
                'total' => $customers->total(),
                'totalOutstanding' => CustomHelper::formatCurrency($totalOutstanding),
                'overdueCustomerCount' => $overdueCustomerCount,
                'totalOverdueAmount' => CustomHelper::formatCurrency($totalOverdueAmount),
            ]);
        }

        // If not AJAX, return full view

        return view('admin.crm.billing_summary.index');
    }
}
