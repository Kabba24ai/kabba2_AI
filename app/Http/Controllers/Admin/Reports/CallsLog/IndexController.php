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
        $calls = CustomerCallNeeded::with([
            'customer',
            'creator',
            'assignee',
            'activities.user',
        ])
        ->latest()
        ->paginate($request->input('per_page', 20))
        ->withQueryString();

        $totalCalls     = CustomerCallNeeded::count();
        $activeCalls    = CustomerCallNeeded::where('status', 'active')->count();
        $completedCalls = CustomerCallNeeded::where('status', 'clear')->count();

        if ($request->ajax()) {
            $html = view('admin.reports.calls_log.partials._table', compact('calls'))->render();

            return response()->json([
                'success'        => true,
                'html'           => $html,
                'totalCalls'     => $totalCalls,
                'activeCalls'    => $activeCalls,
                'completedCalls' => $completedCalls,
            ]);
        }

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->get();

        $users = User::orderBy('first_name')->get();

        return view('admin.reports.calls_log.index', compact(
            'calls',
            'totalCalls',
            'activeCalls',
            'completedCalls',
            'customers',
            'users',
        ));
    }
}
