<?php

namespace App\Livewire\ServiceManagement;

use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Enums\Service\FinancialStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Service Operations Board — the consolidated front-facing dashboard for all
 * service work (shop tickets, field calls, warranty). Modeled on the Queue
 * Line board (server-rendered Livewire, wire:poll live refresh): each row is
 * a technician (the ticket's Team Leader), and within a row the left-to-right
 * order of cards is the priority / work order for the day. Tickets with no
 * team leader sit in the "Unassigned · Intake" lane awaiting dispatch.
 *
 * Phase 1 (this component): live data, KPI tiles, filters, issue-type tabs,
 * inline status change (routes through ServiceTicket::transitionTo), and
 * card → workbench navigation. Drag-to-reorder / drag-to-reassign persistence
 * (board_position + team-leader reassignment) lands in Phase 2.
 */
class OperationsBoard extends Component
{
    public string $typeFilter = 'All';       // All | Damage | Maint. | Warranty | Field
    public ?string $kpiFilter = null;        // open | emergency | blocked | bill
    public string $statusFilter = 'All';
    public string $priorityFilter = 'All';   // All | Emergency | Normal
    public bool $emergencyOnly = false;
    public bool $aging = false;

    public function pollSeconds(): int
    {
        return 30;
    }

    /** Inline status change from a card — routes through the canonical
     *  transition (stamps timestamps, clears/sets blocked context, saves). */
    public function setStatus(int $ticketId, string $status): void
    {
        $target = RepairStatus::tryFrom($status);
        if ($target === null) {
            return;
        }

        $ticket = ServiceTicket::find($ticketId);
        if ($ticket !== null) {
            $ticket->transitionTo($target);
        }
    }

    /**
     * Persist a drag: move ticket $ticketId into lane $laneKey and rewrite the
     * priority order of that lane from $orderedIds (its cards after the drop,
     * left-to-right). $laneKey is a team-leader user id, or 'unassigned'.
     *
     *  - Reassign: the lane owner is the ticket's Team Leader. Moving to a tech
     *    lane makes that tech the team leader (added to the crew if absent);
     *    moving to Unassigned clears the team-leader flag (crew is untouched).
     *  - Reorder: board_position is set to the 1-based index within the lane.
     *    Positions are only ever compared within a lane, so reusing 1..n per
     *    lane is correct; the source lane keeps its remaining order.
     */
    public function moveCard(int $ticketId, string $laneKey, array $orderedIds): void
    {
        $ticket = ServiceTicket::find($ticketId);
        if ($ticket === null) {
            return;
        }

        // Clear any existing team-leader flag on this ticket's crew.
        DB::table('service_ticket_personnel')
            ->where('service_ticket_id', $ticket->id)
            ->update(['is_team_leader' => 0]);

        if ($laneKey !== 'unassigned') {
            $techId = (int) $laneKey;
            if ($techId > 0 && User::whereKey($techId)->exists()) {
                if ($ticket->personnel()->where('users.id', $techId)->exists()) {
                    DB::table('service_ticket_personnel')
                        ->where('service_ticket_id', $ticket->id)
                        ->where('employee_id', $techId)
                        ->update(['is_team_leader' => 1]);
                } else {
                    $ticket->personnel()->attach($techId, ['is_team_leader' => true]);
                }
            }
        }

        // Rewrite the target lane's priority order (1-based, left to right).
        $position = 1;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                ServiceTicket::whereKey($id)->update(['board_position' => $position]);
                $position++;
            }
        }
    }

    public function setType(string $type): void
    {
        $this->typeFilter = $type;
    }

    public function toggleKpi(string $key): void
    {
        $this->kpiFilter = $this->kpiFilter === $key ? null : $key;
    }

    public function toggleEmergency(): void
    {
        $this->emergencyOnly = !$this->emergencyOnly;
    }

    public function toggleAging(): void
    {
        $this->aging = !$this->aging;
    }

    public function clearFilters(): void
    {
        $this->reset(['typeFilter', 'kpiFilter', 'statusFilter', 'priorityFilter', 'emergencyOnly', 'aging']);
        $this->typeFilter = 'All';
        $this->statusFilter = 'All';
        $this->priorityFilter = 'All';
    }

    // ── mapping helpers ────────────────────────────────────────────────
    private function typeColor(ServiceType $t): array
    {
        return match ($t) {
            ServiceType::CustomerDamageRepair => ['#ffedd5', '#c2410c'],
            ServiceType::OemWarrantyRepair    => ['#e0e7ff', '#4f46e5'],
            ServiceType::InspectionDiagnosis  => ['#e0f2fe', '#0369a1'],
            ServiceType::FieldServiceCall     => ['#f3e8ff', '#7c3aed'],
            ServiceType::InternalRepair       => ['#eef2f7', '#475569'],
        };
    }

    private function statusColor(RepairStatus $s): array
    {
        if (in_array($s->value, RepairStatus::blocked(), true)) {
            return ['#fef3c7', '#b45309', '#fde68a'];
        }
        return match ($s) {
            RepairStatus::Diagnosing     => ['#e0f2fe', '#0369a1', '#bae6fd'],
            RepairStatus::InProgress     => ['#ccfbf1', '#0d9488', '#99f6e4'],
            RepairStatus::ReadyForPickup,
            RepairStatus::Completed      => ['#dcfce7', '#15803d', '#bbf7d0'],
            default                      => ['#eef2f7', '#475569', '#e2e8f0'],
        };
    }

    private function laneColor(int $i): array
    {
        // [accent, bg, band, avatarInitialsBg]
        $palette = [
            ['#0d9488', '#ecfdf8', '#d3f4ec'],
            ['#7c3aed', '#f7f2ff', '#ede4ff'],
            ['#b45309', '#fffaf0', '#fdeecb'],
            ['#0369a1', '#eff6ff', '#dbeafe'],
            ['#be123c', '#fff1f2', '#ffe4e6'],
        ];
        return $palette[$i % count($palette)];
    }

    private function matchesType(ServiceTicket $c, string $f): bool
    {
        if ($f === 'All') {
            return true;
        }
        return match ($f) {
            'Damage'   => $c->service_type === ServiceType::CustomerDamageRepair,
            'Maint.'   => in_array($c->service_type, [ServiceType::InternalRepair, ServiceType::InspectionDiagnosis], true),
            'Warranty' => $c->service_type === ServiceType::OemWarrantyRepair,
            'Field'    => $c->service_type === ServiceType::FieldServiceCall,
            default    => true,
        };
    }

    private function matchesKpi(ServiceTicket $c): bool
    {
        return match ($this->kpiFilter) {
            'open'      => true,
            'emergency' => $c->priority === ServicePriority::Emergency,
            'blocked'   => in_array($c->repair_status->value, RepairStatus::blocked(), true),
            'bill'      => $c->financial_status === FinancialStatus::ReadyToBill,
            default     => true,
        };
    }

    private function passes(ServiceTicket $c): bool
    {
        return $this->matchesType($c, $this->typeFilter)
            && $this->matchesKpi($c)
            && ($this->statusFilter === 'All' || $c->repair_status->value === $this->statusFilter)
            && ($this->priorityFilter === 'All'
                || ($this->priorityFilter === 'Emergency'
                    ? $c->priority === ServicePriority::Emergency
                    : $c->priority !== ServicePriority::Emergency))
            && (!$this->emergencyOnly || $c->priority === ServicePriority::Emergency)
            && (!$this->aging || $this->ageDays($c) > 7);
    }

    private function ageDays(ServiceTicket $c): int
    {
        return $c->opened_at ? (int) $c->opened_at->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : 0;
    }

    private function cardData(ServiceTicket $c, int $ordinal): array
    {
        $days   = $this->ageDays($c);
        $type   = $c->service_type;
        [$tbg, $tc] = $this->typeColor($type);
        [$sbg, $sc, $sbd] = $this->statusColor($c->repair_status);

        $customer = $c->customer?->full_name
            ?: ($c->order?->customer_name ?: 'Internal – Yard');

        return [
            'id'          => $c->id,
            'ordinal'     => $ordinal,
            'ticket'      => $c->ticket_number,
            'equip'       => $c->equipment?->equipment_name ?? 'Equipment',
            'equipId'     => $c->equipment?->equipment_id ? '#' . $c->equipment->equipment_id : '',
            'typeLabel'   => $type->label(),
            'typeBg'      => $tbg,
            'typeC'       => $tc,
            'status'      => $c->repair_status->value,
            'statusLabel' => $c->repair_status->label(),
            'statusBg'    => $sbg,
            'statusC'     => $sc,
            'statusBd'    => $sbd,
            'customer'    => $customer,
            'ageLabel'    => $days === 0 ? 'Today' : $days . 'd open',
            'ageOld'      => $days > 7,
            'emergency'   => $c->priority === ServicePriority::Emergency,
            'field'       => $type === ServiceType::FieldServiceCall,
            'warranty'    => $type === ServiceType::OemWarrantyRepair,
            'showUrl'     => route('admin.service-management.tickets.show', $c),
        ];
    }

    public function render()
    {
        $tickets = ServiceTicket::with([
            'equipment:id,equipment_name,equipment_id',
            'personnel:id,first_name,last_name',
            'customer:id,first_name,last_name',
            'order:id,order_number,customer_name',
        ])
            ->whereIn('repair_status', RepairStatus::notFinished())
            ->get();

        // KPI counts are over the full open set, independent of active filters.
        $kpiCounts = [
            'open'      => $tickets->count(),
            'emergency' => $tickets->filter(fn ($c) => $c->priority === ServicePriority::Emergency)->count(),
            'blocked'   => $tickets->filter(fn ($c) => in_array($c->repair_status->value, RepairStatus::blocked(), true))->count(),
            'bill'      => $tickets->filter(fn ($c) => $c->financial_status === FinancialStatus::ReadyToBill)->count(),
        ];

        $typeCounts = [];
        foreach (['All', 'Damage', 'Maint.', 'Warranty', 'Field'] as $key) {
            $typeCounts[$key] = $tickets->filter(fn ($c) => $this->matchesType($c, $key))->count();
        }

        $visible = $tickets->filter(fn ($c) => $this->passes($c));

        // Group into lanes keyed by team leader (Unassigned = no team leader),
        // ordered within a lane by board_position (nulls last) then opened date.
        $grouped = [];
        foreach ($visible as $c) {
            $leader = $c->teamLeader();
            $key = $leader?->id ?? 'unassigned';
            $grouped[$key] ??= ['leader' => $leader, 'cards' => collect()];
            $grouped[$key]['cards']->push($c);
        }

        // Real team-leader lanes first (alphabetical), Unassigned always last.
        $leaderKeys = collect($grouped)->keys()->reject(fn ($k) => $k === 'unassigned')
            ->sortBy(fn ($k) => $grouped[$k]['leader']?->first_name ?? '')->values();

        $lanes = [];
        $i = 0;
        foreach ($leaderKeys as $k) {
            $lanes[] = $this->buildLane($k, $grouped[$k], $this->laneColor($i));
            $i++;
        }
        if (isset($grouped['unassigned'])) {
            $lanes[] = $this->buildLane('unassigned', $grouped['unassigned'], ['#64748b', '#f4f6f9', '#e6ebf1']);
        }

        $statusOptions = collect([['All', 'All Statuses']])
            ->concat(collect(RepairStatus::cases())
                ->reject(fn ($s) => in_array($s, [RepairStatus::Closed, RepairStatus::Cancelled], true))
                ->map(fn ($s) => [$s->value, $s->label()]))
            ->all();

        // The status set offered inline on a card (working lifecycle only).
        $cardStatuses = collect(RepairStatus::cases())
            ->reject(fn ($s) => in_array($s, [RepairStatus::Closed, RepairStatus::Cancelled], true))
            ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
            ->all();

        return view('livewire.service-management.operations-board', [
            'lanes'         => $lanes,
            'kpiCounts'     => $kpiCounts,
            'typeCounts'    => $typeCounts,
            'statusOptions' => $statusOptions,
            'cardStatuses'  => $cardStatuses,
            'anyFilter'     => $this->typeFilter !== 'All' || $this->kpiFilter !== null
                || $this->statusFilter !== 'All' || $this->priorityFilter !== 'All'
                || $this->emergencyOnly || $this->aging,
        ]);
    }

    private function buildLane(string $key, array $group, array $colors): array
    {
        [$accent, $bg, $band] = $colors;
        $leader = $group['leader'];

        $ordered = $group['cards']
            ->sortBy([
                fn ($c) => $c->board_position === null ? 1 : 0,
                fn ($c) => $c->board_position ?? 0,
                fn ($c) => $c->opened_at?->timestamp ?? 0,
            ])
            ->values();

        $name = $leader ? trim($leader->first_name . ' ' . $leader->last_name) : 'Unassigned · Intake';
        $role = $leader ? 'Service Technician' : 'Awaiting dispatch';
        $initials = $leader
            ? strtoupper(mb_substr($leader->first_name ?? '', 0, 1) . mb_substr($leader->last_name ?? '', 0, 1))
            : '•';

        return [
            'key'      => (string) $key,
            'name'     => $name,
            'role'     => $role,
            'initials' => $initials !== '' ? $initials : '•',
            'accent'   => $accent,
            'bg'       => $bg,
            'band'     => $band,
            'count'    => $ordered->count(),
            'cards'    => $ordered->values()->map(fn ($c, $idx) => $this->cardData($c, $idx + 1))->all(),
        ];
    }
}
