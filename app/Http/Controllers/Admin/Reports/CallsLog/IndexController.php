<?php

namespace App\Http\Controllers\Admin\Reports\CallsLog;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = CustomerCallNeeded::with([
            'customer',
            'creator',
            'assignee',
            'activities.user',
        ])->latest();

        if ($request->filled('search_name')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('customer', function ($sub) use ($request) {
                    $sub->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"]);
                })->orWhere('contact_name', 'like', '%' . $request->search_name . '%');
            });
        }

        if ($request->filled('search_company')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('company_name', 'like', '%' . $request->search_company . '%');
            });
        }

        if ($request->filled('search_phone')) {
            $phone = $request->search_phone;
            $query->where(function ($q) use ($phone) {
                $q->whereHas('customer', function ($sub) use ($phone) {
                    $sub->where('phone', 'like', '%' . $phone . '%');
                })->orWhere('contact_phone', 'like', '%' . $phone . '%');
            });
        }

        if ($request->filled('search_admin')) {
            $query->where('created_by', $request->search_admin);
        }

        $calls = $query->paginate($request->input('per_page', 20))->withQueryString();

        if ($request->ajax()) {
            $html = view('admin.reports.calls_log.partials._table', compact('calls'))->render();

            return response()->json(['success' => true, 'html' => $html]);
        }

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->get();

        $users = User::orderBy('first_name')->get();

        $fuelRecords = OrderProduct::with(['order.customer', 'equipment', 'fuelChargeLogs'])
            ->whereNotNull('fuel_total_charge')
            ->where('fuel_total_charge', '>', 0)
            ->whereHas('order')
            ->latest('id')
            ->paginate(20);

        $damageRecords = OrderProduct::with(['order.customer', 'equipment', 'damageChargeLogs'])
            ->where(function ($q) {
                $q->where('damage_charge', '>', 0)->orWhereNotNull('damage_status');
            })
            ->whereHas('order')
            ->latest('id')
            ->paginate(20);

        return view('admin.reports.calls_log.index', compact('calls', 'customers', 'users', 'fuelRecords', 'damageRecords'));
    }
}
