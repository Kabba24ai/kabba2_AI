<?php

namespace App\Http\Controllers\Admin\Reports\CallsLog;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $fuelOpRecords = OrderProduct::with(['order.customer', 'equipment', 'fuelChargeLogs'])
            ->whereNotNull('fuel_total_charge')
            ->where('fuel_total_charge', '>', 0)
            ->whereHas('order')
            ->latest('id')
            ->get();

        $fuelCrmRecords = CustomerAccount::with(['customer', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->latest()
            ->get();

        // $allFuelRecords = $fuelOpRecords
        //     ->map(fn ($r) => ['_source' => 'op',  '_sort_ts' => $r->created_at?->timestamp ?? 0, '_model' => $r])
        //     ->concat(
        //         $fuelCrmRecords->map(fn ($r) => ['_source' => 'crm', '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0, '_model' => $r])
        //     )
        //     ->sortByDesc('_sort_ts')
        //     ->values();


        $allFuelRecords = $fuelOpRecords
    ->map(fn ($r) => [
        '_source' => 'op',
        '_sort_ts' => $r->created_at?->timestamp ?? 0,
        '_model' => $r,
    ])
    ->concat(
        $fuelCrmRecords->map(fn ($r) => [
            '_source' => 'crm',
            '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0,
            '_model' => $r,
        ])
    )
    ->sortByDesc('_sort_ts')
    ->values();

$page = request()->get('page', 1);
$perPage = $request->input('per_page', 20);

$allFuelRecords = new LengthAwarePaginator(
    $allFuelRecords->forPage($page, $perPage),
    $allFuelRecords->count(),
    $perPage,
    $page,
    [
        'path' => request()->url(),
        'query' => request()->query(),
    ]
);

        $damageOpRecords = OrderProduct::with(['order.customer', 'equipment', 'damageChargeLogs'])
            ->where(function ($q) {
                $q->where('damage_charge', '>', 0)->orWhereNotNull('damage_status');
            })
            ->whereHas('order')
            ->latest('id')
            ->get();

        $damageCrmRecords = CustomerAccount::with(['customer', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Damages')
            ->latest()
            ->get();

        $allDamageRecords = $damageOpRecords
            ->map(fn ($r) => ['_source' => 'op',  '_sort_ts' => $r->created_at?->timestamp ?? 0, '_model' => $r])
            ->concat(
                $damageCrmRecords->map(fn ($r) => ['_source' => 'crm', '_sort_ts' => ($r->date ?? $r->created_at)?->timestamp ?? 0, '_model' => $r])
            )
            ->sortByDesc('_sort_ts')
            ->values();



            $page = request()->get('damage_page', 1);
$perPage = $request->input('per_page', 20);

$allDamageRecords = new LengthAwarePaginator(
    $allDamageRecords->forPage($page, $perPage),
    $allDamageRecords->count(),
    $perPage,
    $page,
    [
        'path' => request()->url(),
        'query' => request()->query(),
        'pageName' => 'damage_page',
    ]
);


        return view('admin.reports.calls_log.index', compact('calls', 'customers', 'users', 'allFuelRecords', 'allDamageRecords'));
    }
}
