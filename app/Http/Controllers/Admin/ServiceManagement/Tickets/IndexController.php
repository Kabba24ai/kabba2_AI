<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = ServiceTicket::with(['personnel', 'equipment', 'order', 'customer']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        foreach (['priority', 'service_type', 'repair_status', 'financial_responsibility', 'financial_status'] as $col) {
            if ($request->filled($col)) {
                $query->where($col, $request->input($col));
            }
        }

        if ($request->filled('technician')) {
            $query->whereHas('personnel', fn ($p) => $p->where('users.id', $request->input('technician')));
        }
        if ($request->filled('equipment')) {
            $query->where('equipment_id', $request->input('equipment'));
        }
        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($o) => $o->where('order_number', 'like', '%' . $request->input('order_number') . '%'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->input('date_to'));
        }

        // Active / Blocked / Closed state filter (same blocked logic as Phase 1)
        $state = $request->input('state', '');
        if ($state === 'active') {
            $query->activeQueue();
        } elseif ($state === 'blocked') {
            $query->blockedQueue();
        } elseif ($state === 'closed') {
            $query->whereNotIn('repair_status', RepairStatus::notFinished());
        }

        // Default sort: active group first, then blocked, then finished;
        // within each group priority first, oldest opened date second.
        $activeList  = "'" . implode("','", RepairStatus::active()) . "'";
        $blockedList = "'" . implode("','", RepairStatus::blocked()) . "'";

        $tickets = $query
            ->orderByRaw("CASE WHEN repair_status IN ({$activeList}) THEN 0 WHEN repair_status IN ({$blockedList}) THEN 1 ELSE 2 END")
            ->orderByRaw(ServicePriority::sqlOrder())
            ->orderBy('opened_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.service_management.tickets.index', [
            'tickets'       => $tickets,
            'employees'     => User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'equipmentList' => Equipment::orderBy('equipment_name')->get(['id', 'equipment_name', 'equipment_id']),
        ]);
    }
}
