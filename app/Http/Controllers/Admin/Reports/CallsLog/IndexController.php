<?php

namespace App\Http\Controllers\Admin\Reports\CallsLog;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
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
        ])
        ->orderByRaw('COALESCE(
            (SELECT MAX(a.created_at) FROM customer_call_needed_activities a WHERE a.customer_call_needed_id = customer_call_neededs.id),
            customer_call_neededs.created_at
        ) DESC');

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
            $phone      = $request->search_phone;
            $digitsOnly = preg_replace('/\D/', '', $phone);
            $query->where(function ($q) use ($phone, $digitsOnly) {
                $q->whereHas('customer', function ($sub) use ($phone, $digitsOnly) {
                    $sub->where('phone', 'like', '%' . $phone . '%')
                        ->orWhere('phone', 'like', '%' . $digitsOnly . '%');
                })->orWhere('contact_phone', 'like', '%' . $phone . '%')
                  ->orWhere('contact_phone', 'like', '%' . $digitsOnly . '%');
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

        // Damage tab loads via AJAX on first tab activation — no pre-load
        // needed. (The legacy Fuel tab is retired: fuel charges are managed
        // in the Fuel Charge Workspace.)
        $allDamageRecords = collect();

        return view('admin.reports.calls_log.index', compact('calls', 'customers', 'users', 'allDamageRecords'));
    }
}
