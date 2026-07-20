<?php

namespace App\Livewire\QueueLine;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineItem;
use App\Models\Stores\Store;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineEligibility;
use App\Services\QueueLine\QueueLineService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Queue Line board (Phase 1).
 *
 * Presentation + action dispatch ONLY — every eligibility/ordering rule
 * lives in QueueLineEligibility, every state write in QueueLineService.
 * Built as Livewire from day one so Phase 4 auto-refresh is a poll-attribute
 * change, not a rewrite (dashboard wire:poll precedent). Actions re-render
 * the component (no page reload) and the store filter is a Livewire
 * property, so it survives every action automatically.
 */
class Board extends Component
{
    /**
     * Poll intervals per mode — centralized here (spec §10) so standard vs
     * wall-board cadence is one explicit decision, not scattered literals.
     */
    public const POLL_SECONDS_STANDARD = 60;

    public const POLL_SECONDS_WALLBOARD = 30;

    /** Store filter: 'all' or a store id (string — HTML select values). */
    public string $store = 'all';

    public bool $showSuppressed = false;

    public ?string $actionError = null;

    /**
     * Wall-board mode: same component, same eligibility, same actions —
     * only presentation scale, chrome, and poll cadence differ.
     */
    public bool $wallboard = false;

    public function pollSeconds(): int
    {
        return $this->wallboard ? self::POLL_SECONDS_WALLBOARD : self::POLL_SECONDS_STANDARD;
    }

    public function stage(int $orderProductId): void
    {
        $this->act($orderProductId, 'stage');
    }

    public function unstage(int $orderProductId): void
    {
        $this->act($orderProductId, 'unstage');
    }

    public function rush(int $orderProductId): void
    {
        $this->act($orderProductId, 'rush');
    }

    public function unrush(int $orderProductId): void
    {
        $this->act($orderProductId, 'unrush');
    }

    public function removeToday(int $orderProductId): void
    {
        $this->act($orderProductId, 'removeToday');
    }

    public function removeForever(int $orderProductId): void
    {
        $this->act($orderProductId, 'removeForever');
    }

    public function restoreItem(int $orderProductId): void
    {
        $this->act($orderProductId, 'restore');
    }

    // ── Switch Equipment (Phase 3A) — canonical reassignment ────────────

    public ?int $switchingItemId = null;

    public string $switchSearch = '';

    public string $switchPerformedBy = '';

    public string $switchReason = '';

    public ?string $switchError = null;

    public ?string $actionNotice = null;

    public function openSwitch(int $orderProductId): void
    {
        $this->switchingItemId = $orderProductId;
        $this->switchSearch = '';
        $this->switchPerformedBy = '';
        $this->switchReason = '';
        $this->switchError = null;
        $this->actionNotice = null;
    }

    public function closeSwitch(): void
    {
        $this->switchingItemId = null;
        $this->switchError = null;
    }

    public function confirmSwitch(int $equipmentId): void
    {
        $this->switchError = null;

        try {
            $orderProduct = OrderProduct::with('softAssignment.equipment', 'order')
                ->findOrFail($this->switchingItemId);
            $replacement = Equipment::findOrFail($equipmentId);

            $performedBy = User::active()->find((int) $this->switchPerformedBy);
            if (! $performedBy) {
                $this->switchError = 'Select yourself from the employee list before confirming.';

                return;
            }

            $result = EquipmentReassignmentService::switch(
                orderProduct: $orderProduct,
                replacement: $replacement,
                performedBy: $performedBy,
                actor: auth()->user(),
                source: EquipmentReassignmentService::SOURCE_WEB,
                reason: trim($this->switchReason) ?: null,
            );

            $notice = $result['changed']
                ? "Equipment switched to {$result['replacement']->equipment_name}."
                : "{$result['replacement']->equipment_name} is already assigned to this item.";

            if ($result['conflicts']->isNotEmpty()) {
                $notice .= ' Note: this creates ' . $result['conflicts']->count()
                    . ' scheduling conflict(s) — flagged on Schedule Conflicts for admin review.';
            }

            $this->actionNotice = $notice;
            $this->closeSwitch();
        } catch (\InvalidArgumentException $e) {
            $this->switchError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->switchError = 'The switch could not be completed. Please refresh and try again.';
        }
    }

    // ── Operational history drawer (Phase 4 §5) ─────────────────────────

    public ?int $historyItemId = null;

    public function openHistory(int $orderProductId): void
    {
        $this->historyItemId = $orderProductId;
        $this->actionNotice = null;
    }

    public function closeHistory(): void
    {
        $this->historyItemId = null;
    }

    // ── Queue Fuel Verification (Phase 3B) ───────────────────────────────

    public ?int $fuelItemId = null;

    /** 'verify' | 'reverse' */
    public string $fuelMode = 'verify';

    /** Deliberately NOT reset between items — shared-terminal employees
     *  verify several machines in a row (approved selector convention). */
    public string $fuelPerformedBy = '';

    public string $fuelReason = '';

    public ?string $fuelError = null;

    public function openFuelVerify(int $orderProductId): void
    {
        $this->fuelItemId = $orderProductId;
        $this->fuelMode = 'verify';
        $this->fuelReason = '';
        $this->fuelError = null;
        $this->actionNotice = null;
    }

    public function openFuelReverse(int $orderProductId): void
    {
        $this->fuelItemId = $orderProductId;
        $this->fuelMode = 'reverse';
        $this->fuelReason = '';
        $this->fuelError = null;
        $this->actionNotice = null;
    }

    public function closeFuel(): void
    {
        $this->fuelItemId = null;
        $this->fuelError = null;
    }

    /**
     * @param  int  $expectedEquipmentId  the unit shown on screen — the service
     *                                    rejects it if the assignment moved on
     */
    public function confirmFuelVerify(int $expectedEquipmentId): void
    {
        $this->fuelError = null;

        try {
            $orderProduct = OrderProduct::with('softAssignment.equipment', 'order', 'queueLineItem')
                ->findOrFail($this->fuelItemId);
            $expected = Equipment::findOrFail($expectedEquipmentId);

            $performedBy = User::active()->find((int) $this->fuelPerformedBy);
            if (! $performedBy) {
                $this->fuelError = 'Select yourself from the employee list before confirming.';

                return;
            }

            $result = QueueFuelVerificationService::verify(
                orderProduct: $orderProduct,
                expected: $expected,
                performedBy: $performedBy,
                actor: auth()->user(),
                source: $this->wallboard ? QueueLineFuelVerification::SOURCE_WALL : QueueLineFuelVerification::SOURCE_WEB,
            );

            $this->actionNotice = "Fuel Full verified for {$expected->equipment_name} by {$performedBy->full_name}.";
            $this->closeFuel();
        } catch (\InvalidArgumentException $e) {
            $this->fuelError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->fuelError = 'The verification could not be saved. Please refresh and try again.';
        }
    }

    public function confirmFuelReverse(int $verificationId): void
    {
        $this->fuelError = null;

        try {
            $verification = QueueLineFuelVerification::findOrFail($verificationId);

            $performedBy = User::active()->find((int) $this->fuelPerformedBy);
            if (! $performedBy) {
                $this->fuelError = 'Select yourself from the employee list before confirming.';

                return;
            }

            QueueFuelVerificationService::reverse(
                verification: $verification,
                performedBy: $performedBy,
                actor: auth()->user(),
                reason: trim($this->fuelReason),
                source: $this->wallboard ? QueueLineFuelVerification::SOURCE_WALL : QueueLineFuelVerification::SOURCE_WEB,
            );

            $this->actionNotice = 'Fuel verification reversed — the unit must be verified again before release.';
            $this->closeFuel();
        } catch (\InvalidArgumentException $e) {
            $this->fuelError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->fuelError = 'The reversal could not be saved. Please refresh and try again.';
        }
    }

    /** Candidate units for the switch modal — direct matches first, barcode-exact on top. */
    private function switchCandidates(OrderProduct $item): Collection
    {
        $term = trim($this->switchSearch);

        return Equipment::query()
            ->where('current_status', '!=', 'rented')
            ->when($item->softAssignment?->equipment_id, fn ($q, $current) => $q->where('id', '!=', $current))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($q) use ($term) {
                    $q->where('equipment_id', 'like', "%{$term}%")
                        ->orWhere('equipment_name', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%");
                });
            })
            ->with('assignedProduct:id,product_name')
            ->orderByRaw('equipment_id = ? DESC', [$term])                              // barcode scan → exact id first
            ->orderByRaw('assigned_product_id = ? DESC', [$item->product_id])           // direct matches next
            ->orderBy('equipment_name')
            ->limit(15)
            ->get();
    }

    public function render()
    {
        $error = null;
        $sections = [];
        $suppressed = collect();

        try {
            $storeId = $this->store === 'all' ? null : (int) $this->store;

            $rows = QueueLineEligibility::sortItems(
                QueueLineEligibility::boardQuery($storeId)->get()
            );

            $sections = $this->buildSections($rows);
            $suppressed = $this->suppressedItems();
        } catch (\Throwable $e) {
            report($e);
            $error = 'The Queue Line board could not be loaded. Please refresh — if this keeps happening, contact support.';
        }

        // Current fuel state for every visible card in ONE query — a row is
        // current only when keyed to the item's LIVE soft-assign episode
        // (matched in the card partial against softAssignment->id).
        $fuelByAssignment = QueueLineFuelVerification::query()
            ->whereIn('order_product_id', collect($sections)->flatten(3)->whereInstanceOf(OrderProduct::class)->pluck('id')->all() ?: [0])
            ->where('action', QueueLineFuelVerification::ACTION_VERIFIED)
            ->whereDoesntHave('reversal')
            ->with('performedBy:id,first_name,last_name')
            ->get()
            ->keyBy('equipment_soft_assign_id');

        $switchingItem = null;
        $switchCandidates = collect();
        $activeEmployees = collect();

        $fuelItem = null;
        $fuelCurrent = null;
        $fuelHistory = collect();

        if ($this->fuelItemId) {
            $fuelItem = OrderProduct::with('softAssignment.equipment', 'product:id,product_name', 'order')
                ->find($this->fuelItemId);

            if ($fuelItem) {
                $fuelCurrent = QueueFuelVerificationService::currentVerification($fuelItem);
                $fuelHistory = QueueFuelVerificationService::history($fuelItem);
                $activeEmployees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
            } else {
                $this->fuelItemId = null;
            }
        }

        $historyItem = null;
        $historyEvents = collect();

        if ($this->historyItemId) {
            $historyItem = OrderProduct::with('softAssignment.equipment', 'product:id,product_name', 'order')
                ->find($this->historyItemId);

            if ($historyItem) {
                $historyEvents = \App\Services\QueueLine\QueueLineHistory::timeline($historyItem);
            } else {
                $this->historyItemId = null;
            }
        }

        if ($this->switchingItemId) {
            $switchingItem = OrderProduct::with('softAssignment.equipment', 'product:id,product_name', 'order')
                ->find($this->switchingItemId);

            if ($switchingItem) {
                $switchCandidates = $this->switchCandidates($switchingItem);
                $activeEmployees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
            } else {
                $this->switchingItemId = null;
            }
        }

        return view('livewire.queue-line.board', [
            'sections' => $sections,
            'suppressedItems' => $suppressed,
            'stores' => Store::active()->orderBy('store_name')->get(['id', 'store_name']),
            'boardError' => $error,
            'lastUpdated' => now(), // re-stamped by every poll/action render
            'switchingItem' => $switchingItem,
            'switchCandidates' => $switchCandidates,
            'activeEmployees' => $activeEmployees,
            'fuelByAssignment' => $fuelByAssignment,
            'fuelItem' => $fuelItem,
            'fuelCurrent' => $fuelCurrent,
            'fuelHistory' => $fuelHistory,
            'historyItem' => $historyItem,
            'historyEvents' => $historyEvents,
            'summary' => $this->summarize($sections, $fuelByAssignment),
        ]);
    }

    /**
     * Wall-board summary strip (Phase 4 §7) — every count is derived from the
     * ALREADY-LOADED board collection + the single batched fuel lookup. No
     * additional queries, and the counts inherit the store filter for free.
     *
     * @param  array<string, array<int, array{order: mixed, items: array<int, OrderProduct>}>>  $sections
     * @param  Collection  $fuelByAssignment  current verifications keyed by soft-assign episode id
     * @return array<string, int>
     */
    private function summarize(array $sections, Collection $fuelByAssignment): array
    {
        $summary = [
            'cards' => 0,
            'rush' => 0, 'overdue' => 0, 'today' => 0, 'tomorrow' => 0,
            'needsEquipment' => 0,
            'fuelNotVerified' => 0,
            'alternate' => 0,
            'unknown' => 0,
            'maintenanceHold' => 0,
            'damaged' => 0,
        ];

        foreach ($sections as $sectionKey => $groups) {
            foreach ($groups as $group) {
                foreach ($group['items'] as $item) {
                    $summary['cards']++;
                    $summary[$sectionKey]++;

                    $classification = QueueLineEligibility::classifyAssignment($item);

                    if ($classification === QueueLineEligibility::ASSIGNMENT_UNASSIGNED) {
                        $summary['needsEquipment']++;

                        continue; // fuel/equipment metrics need an assigned unit
                    }

                    if ($classification === QueueLineEligibility::ASSIGNMENT_ALTERNATE) {
                        $summary['alternate']++;
                    } elseif ($classification === QueueLineEligibility::ASSIGNMENT_UNKNOWN) {
                        $summary['unknown']++;
                    }

                    if (! isset($fuelByAssignment[$item->softAssignment->id])) {
                        $summary['fuelNotVerified']++;
                    }

                    $equipment = $item->softAssignment->equipment;

                    if ($equipment?->current_status?->value === 'maintenance') {
                        $summary['maintenanceHold']++;
                    }

                    if ($equipment?->current_status?->value === 'damaged'
                        || QueueLineEligibility::rentalReadyLabel($item) === QueueLineEligibility::RR_DAMAGED) {
                        $summary['damaged']++;
                    }
                }
            }
        }

        return $summary;
    }

    /**
     * RUSH / Overdue / Today / Tomorrow sections, each grouped by order.
     * A rushed item lives ONLY in the RUSH section; its non-rushed siblings
     * stay in their date buckets (RUSH is item-specific). Group order inside
     * a section follows the first (highest-priority) item of each order.
     */
    private function buildSections(Collection $rows): array
    {
        $sections = ['rush' => [], 'overdue' => [], 'today' => [], 'tomorrow' => []];

        foreach ($rows as $row) {
            $section = $row->queueLineItem?->isRushed()
                ? 'rush'
                : QueueLineEligibility::bucketFor($row);

            $orderId = $row->order_id;

            if (! isset($sections[$section][$orderId])) {
                $sections[$section][$orderId] = ['order' => $row->order, 'items' => []];
            }

            $sections[$section][$orderId]['items'][] = $row;
        }

        return $sections;
    }

    /** Removed-Forever drawer: every suppressed item, regardless of current window. */
    private function suppressedItems(): Collection
    {
        return QueueLineItem::where('suppressed_forever', true)
            ->with(['orderProduct.product:id,product_name', 'order'])
            ->orderByDesc('suppressed_forever_at')
            ->get()
            ->filter(fn (QueueLineItem $item) => $item->orderProduct !== null && $item->order !== null)
            ->values();
    }

    private function act(int $orderProductId, string $method): void
    {
        $this->actionError = null;

        try {
            $orderProduct = OrderProduct::with('softAssignment.equipment')->findOrFail($orderProductId);
            QueueLineService::{$method}($orderProduct, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->actionError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->actionError = 'That action could not be completed. Please refresh and try again.';
        }
    }
}
