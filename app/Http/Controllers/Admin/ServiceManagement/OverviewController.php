<?php

namespace App\Http\Controllers\Admin\ServiceManagement;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketResourceDispatch;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    /**
     * Service Operations dashboard (Phase 1).
     * KPI cards, shop/field/financial summaries, active + blocked work
     * queues, and computed service alerts.
     */
    public function __invoke(Request $request)
    {
        $withRelations = ['personnel', 'equipment', 'order', 'customer'];

        // ── Work queues ───────────────────────────────────────────────
        // Active: priority first, then oldest ticket. Blocked tickets are
        // excluded here entirely — an Emergency ticket waiting on parts
        // must not sit on top of the actionable list.
        $activeQueue = ServiceTicket::with($withRelations)
            ->activeQueue()
            ->orderByRaw(ServicePriority::sqlOrder())
            ->orderBy('opened_at')
            ->get();

        // Blocked: what unblocks soonest first, then priority, then age.
        $blockedQueue = ServiceTicket::with($withRelations)
            ->blockedQueue()
            ->orderByRaw('expected_action_date IS NULL, expected_action_date ASC')
            ->orderByRaw(ServicePriority::sqlOrder())
            ->orderBy('opened_at')
            ->get();

        // ── KPI cards ─────────────────────────────────────────────────
        $kpis = [
            'open'         => ServiceTicket::open()->count(),
            'emergency'    => ServiceTicket::open()->where('priority', ServicePriority::Emergency->value)->count(),
            'blocked'      => $blockedQueue->count(),
            'ready_to_bill'=> ServiceTicket::where('financial_status', FinancialStatus::ReadyToBill->value)->count(),
        ];

        // ── Summary cards ─────────────────────────────────────────────
        $statusCounts = ServiceTicket::open()
            ->selectRaw('repair_status, COUNT(*) as cnt')
            ->groupBy('repair_status')
            ->pluck('cnt', 'repair_status');

        $shop = [
            'Open Tickets'        => (int) $statusCounts->get(RepairStatus::Open->value, 0),
            'In Progress'         => (int) $statusCounts->get(RepairStatus::InProgress->value, 0),
            'Waiting on Parts'    => (int) $statusCounts->get(RepairStatus::WaitingOnParts->value, 0),
            'Waiting on Warranty' => (int) $statusCounts->get(RepairStatus::WaitingOnWarrantyApproval->value, 0),
            'Ready for Pickup'    => (int) $statusCounts->get(RepairStatus::ReadyForPickup->value, 0),
        ];

        $activeFieldTicketIds = ServiceTicket::open()
            ->where('service_type', ServiceType::FieldServiceCall->value)
            ->pluck('id');

        $outDispatches = ServiceTicketResourceDispatch::currentlyOut()
            ->whereIn('service_ticket_id', $activeFieldTicketIds)
            ->get();

        $field = [
            'Active Field Calls'     => $activeFieldTicketIds->count(),
            'Technicians Dispatched' => $outDispatches->whereNotNull('employee_id')->unique('employee_id')->count(),
            'Vehicles Dispatched'    => $outDispatches->whereNotNull('vehicle_id')->unique('vehicle_id')->count(),
            'Equipment Assigned'     => $outDispatches->whereNotNull('equipment_id')->unique('equipment_id')->count(),
        ];

        $financial = [
            'Customer Billing Pending' => ServiceTicket::where('financial_responsibility', FinancialResponsibility::CustomerPay->value)
                ->where('financial_status', FinancialStatus::ReadyToBill->value)->count(),
            'Warranty Claims Pending'  => ServiceTicket::whereIn('financial_status', FinancialStatus::warrantyPending())->count(),
            'Outstanding Payments'     => ServiceTicket::whereIn('financial_status', FinancialStatus::outstanding())->count(),
            'Paid Service Charges MTD' => ServiceTicket::where('financial_status', FinancialStatus::Paid->value)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()])->count(),
        ];

        // ── Service alerts (computed from ticket state) ───────────────
        $alerts = [];

        ServiceTicket::open()
            ->where('priority', ServicePriority::Emergency->value)
            ->whereDoesntHave('personnel')
            ->get()
            ->each(function ($t) use (&$alerts) {
                $alerts[] = [
                    'level' => 'red',
                    'text'  => "Emergency ticket {$t->ticket_number} has no technician assigned.",
                ];
            });

        $blockedQueue
            ->filter(fn ($t) => $t->expected_action_date && $t->expected_action_date->isPast())
            ->each(function ($t) use (&$alerts) {
                $alerts[] = [
                    'level' => 'amber',
                    'text'  => "Ticket {$t->ticket_number} passed its expected action date ("
                        . $t->expected_action_date->format('M j') . ") — still waiting on "
                        . $t->repair_status->waitingOnLabel() . '.',
                ];
            });

        $blockedQueue
            ->filter(fn ($t) => $t->repair_status === RepairStatus::WaitingOnCustomerApproval
                && $t->age_days >= 5)
            ->each(function ($t) use (&$alerts) {
                $alerts[] = [
                    'level' => 'amber',
                    'text'  => "Ticket {$t->ticket_number} has waited {$t->age_days} days on customer approval.",
                ];
            });

        // Warranty approved while the repair is still marked waiting — work can resume.
        ServiceTicket::open()
            ->where('financial_status', FinancialStatus::WarrantyApproved->value)
            ->where('repair_status', RepairStatus::WaitingOnWarrantyApproval->value)
            ->get()
            ->each(function ($t) use (&$alerts) {
                $alerts[] = [
                    'level' => 'green',
                    'text'  => "Warranty approved for ticket {$t->ticket_number} — repair can resume.",
                ];
            });

        return view('admin.service_management.overview', compact(
            'kpis',
            'shop',
            'field',
            'financial',
            'activeQueue',
            'blockedQueue',
            'alerts',
        ));
    }
}
