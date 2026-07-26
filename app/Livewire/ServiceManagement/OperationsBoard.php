<?php

namespace App\Livewire\ServiceManagement;

use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\ServiceTicketEventType;
use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    public string $technicianFilter = 'All'; // All | <user id> | unassigned
    public string $search = '';              // free text: ticket #, equipment, customer
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
     * SAME-LANE reorder only: rewrite the priority order of a lane from
     * $orderedIds (its cards after the drop, left-to-right). Never touches
     * assignment — the lane owner is unchanged, so a same-lane drag is a pure
     * priority change. Cross-lane moves go through reassignCard() after an
     * explicit confirmation (see the board's drag handler).
     */
    public function moveCard(int $ticketId, string $laneKey, array $orderedIds): void
    {
        $this->applyLanePositions($orderedIds);
    }

    /**
     * CROSS-LANE move = an assignment change (the lane IS the canonical team
     * leader). Confirmed on the client before this runs. Reassigns the team
     * leader on service_ticket_personnel, keeps the Field Service companion
     * technician in step, records an audit event, and persists the new lane
     * order — all atomically, so the canonical record and the board can never
     * drift. $laneKey is the target technician's user id, or 'unassigned'.
     */
    public function reassignCard(int $ticketId, string $laneKey, array $orderedIds): void
    {
        // Server-authoritative guard — a manipulated request cannot reassign
        // work without an authenticated, authorized operator. (The manage
        // permission is inert under the module-wide Gate bypass today, but the
        // check is in place for when enforcement is switched on.)
        abort_unless(auth()->check(), 403);
        abort_if(Gate::denies('service_tickets.manage'), 403);

        $ticket = ServiceTicket::with(['personnel', 'fieldServiceTicket'])->find($ticketId);
        if ($ticket === null) {
            return;
        }

        $currentLeader = $ticket->teamLeader();
        $newLeaderId   = $laneKey === 'unassigned' ? null : ((int) $laneKey ?: null);

        if ($newLeaderId !== null && !User::whereKey($newLeaderId)->exists()) {
            return; // unknown technician — reject
        }

        // No actual ownership change (e.g. a stray cross-lane event that
        // resolves to the same leader) → treat as a pure reorder.
        if (($currentLeader?->id) === $newLeaderId) {
            $this->applyLanePositions($orderedIds);
            return;
        }

        DB::transaction(function () use ($ticket, $currentLeader, $newLeaderId, $orderedIds) {
            // Reassign the team leader on the canonical crew pivot. Other crew
            // members are preserved — only the is_team_leader flag moves.
            DB::table('service_ticket_personnel')
                ->where('service_ticket_id', $ticket->id)
                ->update(['is_team_leader' => 0]);

            if ($newLeaderId !== null) {
                if ($ticket->personnel()->where('users.id', $newLeaderId)->exists()) {
                    DB::table('service_ticket_personnel')
                        ->where('service_ticket_id', $ticket->id)
                        ->where('employee_id', $newLeaderId)
                        ->update(['is_team_leader' => 1]);
                } else {
                    $ticket->personnel()->attach($newLeaderId, ['is_team_leader' => true]);
                }
            }

            // Field Service companion: keep its technician in step with the
            // canonical assignment. updateQuietly avoids the field→companion
            // sync hook (we just set the companion leader directly above).
            if ($ticket->service_type === ServiceType::FieldServiceCall && $ticket->fieldServiceTicket) {
                $ticket->fieldServiceTicket->updateQuietly(['technician_id' => $newLeaderId]);
            }

            ServiceTicketEvent::record(
                $ticket->id,
                ServiceTicketEventType::TechnicianReassigned,
                old: $currentLeader?->full_name ?? 'Unassigned',
                new: $newLeaderId ? (User::find($newLeaderId)?->full_name ?? "user #{$newLeaderId}") : 'Unassigned',
                notes: 'Reassigned via Service Operations Board drag-and-drop.',
                metadata: [
                    'previous_technician_id' => $currentLeader?->id,
                    'new_technician_id'      => $newLeaderId,
                    'source'                 => 'operations_board_dnd',
                ],
            );

            // Destination lane: honor the drop order the client sent.
            $this->applyLanePositions($orderedIds);

            // Source lane: the card left a gap — renumber its remaining cards so
            // both lanes stay sequential and deterministic after the move.
            $this->normalizeLane($currentLeader?->id);
        });
    }

    /** Rewrite a lane's priority order (1-based, left to right). */
    private function applyLanePositions(array $orderedIds): void
    {
        $position = 1;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                ServiceTicket::whereKey($id)->update(['board_position' => $position]);
                $position++;
            }
        }
    }

    /**
     * Resequence a whole lane (1..n) from its current on-board order — the same
     * board_position-then-age ordering the board renders with. $leaderId is the
     * lane's team leader, or null for the Unassigned lane. Used to close the
     * gap the moved card left in its source lane.
     */
    private function normalizeLane(?int $leaderId): void
    {
        $query = ServiceTicket::whereIn('repair_status', RepairStatus::notFinished());

        if ($leaderId !== null) {
            $query->whereHas('personnel', fn ($p) => $p
                ->where('users.id', $leaderId)
                ->where('service_ticket_personnel.is_team_leader', true));
        } else {
            $query->whereDoesntHave('personnel', fn ($p) => $p
                ->where('service_ticket_personnel.is_team_leader', true));
        }

        $ids = $query
            ->orderByRaw('board_position IS NULL, board_position ASC')
            ->orderBy('opened_at')
            ->pluck('id')
            ->all();

        $this->applyLanePositions($ids);
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
        $this->reset(['typeFilter', 'kpiFilter', 'statusFilter', 'priorityFilter', 'technicianFilter', 'search', 'emergencyOnly', 'aging']);
        $this->typeFilter = 'All';
        $this->statusFilter = 'All';
        $this->priorityFilter = 'All';
        $this->technicianFilter = 'All';
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

    private function matchesTechnician(ServiceTicket $c): bool
    {
        if ($this->technicianFilter === 'All') {
            return true;
        }
        $leaderId = $c->teamLeader()?->id;
        if ($this->technicianFilter === 'unassigned') {
            return $leaderId === null;
        }
        return (string) $leaderId === $this->technicianFilter;
    }

    private function matchesSearch(ServiceTicket $c): bool
    {
        $q = trim($this->search);
        if ($q === '') {
            return true;
        }
        $q = mb_strtolower($q);
        $haystacks = array_filter([
            $c->ticket_number,
            $c->equipment?->equipment_name,
            $c->equipment?->equipment_id,
            $c->customer?->full_name,
            $c->order?->customer_name,
        ]);
        foreach ($haystacks as $h) {
            if (str_contains(mb_strtolower((string) $h), $q)) {
                return true;
            }
        }
        return false;
    }

    private function passes(ServiceTicket $c): bool
    {
        return $this->matchesType($c, $this->typeFilter)
            && $this->matchesKpi($c)
            && $this->matchesTechnician($c)
            && $this->matchesSearch($c)
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

        $isBlocked = in_array($c->repair_status->value, RepairStatus::blocked(), true);

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
            'blocked'     => $isBlocked,
            'waitingLabel' => $isBlocked ? 'Waiting on ' . $c->repair_status->waitingOnLabel() : null,
            'showUrl'     => $this->workbenchUrl($c),
        ];
    }

    /**
     * The correct workbench for a card. Field Service work is a canonical
     * service_ticket with a companion field_service_ticket — its card opens
     * the specialized FIELD workbench (dispatch/route/on-site), resolved
     * through the companion. Everything else opens the shop workbench.
     */
    private function workbenchUrl(ServiceTicket $c): string
    {
        if ($c->service_type === ServiceType::FieldServiceCall && $c->fieldServiceTicket) {
            return route('admin.field-service.tickets.show', $c->fieldServiceTicket);
        }
        return route('admin.service-management.tickets.show', $c);
    }

    public function render()
    {
        $tickets = ServiceTicket::with([
            'equipment:id,equipment_name,equipment_id',
            'personnel:id,first_name,last_name',
            'customer:id,first_name,last_name',
            'order:id,order_number,customer_name',
            'fieldServiceTicket:id,service_ticket_id',
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

        // Technician filter options — team leaders currently holding open work,
        // plus an Unassigned bucket. Sorted by first name.
        $technicianOptions = collect([['All', 'All Technicians']]);
        $leaders = $tickets->map(fn ($c) => $c->teamLeader())->filter()->unique('id')
            ->sortBy('first_name')
            ->map(fn ($u) => [(string) $u->id, trim($u->first_name . ' ' . $u->last_name)]);
        $technicianOptions = $technicianOptions->concat($leaders);
        if ($tickets->contains(fn ($c) => $c->teamLeader() === null)) {
            $technicianOptions->push(['unassigned', 'Unassigned']);
        }

        return view('livewire.service-management.operations-board', [
            'lanes'             => $lanes,
            'kpiCounts'         => $kpiCounts,
            'typeCounts'        => $typeCounts,
            'statusOptions'     => $statusOptions,
            'cardStatuses'      => $cardStatuses,
            'technicianOptions' => $technicianOptions->all(),
            'anyFilter'         => $this->typeFilter !== 'All' || $this->kpiFilter !== null
                || $this->statusFilter !== 'All' || $this->priorityFilter !== 'All'
                || $this->technicianFilter !== 'All' || trim($this->search) !== ''
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
