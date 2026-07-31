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

    // ── Filter Expansion (2026-07-20) — presentation NARROWING only. None
    // of these change Queue Line eligibility; they subset the already-
    // eligible rows, and every section count derives from the same
    // filtered collection so totals can never disagree with cards. ──────

    /** Time: 'all' (default — everything eligible, i.e. overdue + today +
     *  tomorrow under the current window) | 'today' ("Today Only":
     *  overdue + today). */
    public string $time = 'all';

    /** Delivery method: 'all' | 'Truck' | 'Store' — the same canonical
     *  delivery_transport_mode values Schedule and Dispatch filter on. */
    public string $method = 'all';

    /** Payment: 'all' | 'paid' | 'pending'. Filters on the SAME
     *  order.last_payment_status the card's payment badge renders, so the
     *  filter can never disagree with what the card shows: 'paid' = the
     *  latest payment row is Paid (the "Paid in Full" badge); 'pending' =
     *  everything else — Pending, Partial Payment, invoice/account rows,
     *  and orders with no payment recorded. A clean binary partition:
     *  Paid + Pending always equals All. */
    public string $payment = 'all';

    /** Dependent Category → Product pair — validated server-side through
     *  ProductFilterHelper exactly like the Orders and Schedule pages.
     *  Product always filters the ORDERED product (product_id), never the
     *  assigned/substituted equipment's mapping. */
    public string $category = '';

    public string $product = '';

    /** Canonical dependency rule: a category change drops an incompatible
     *  product selection (same normalization the Orders/Schedule filters
     *  use); compatible selections survive untouched. */
    public function updatedCategory(): void
    {
        $categoryId = \App\Helpers\ProductFilterHelper::normalizeCategoryId($this->category);

        if (\App\Helpers\ProductFilterHelper::normalizeProductId($this->product, $categoryId) === null) {
            $this->product = '';
        }
    }

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

    // Dependent Category → Product filters for the picker. Category is
    // preselected to the unit currently assigned (99% pick a same-category
    // alternate). Same canonical sources as the board's own filter bar.
    public string $switchCategory = '';

    public string $switchProduct = '';

    public string $switchPerformedBy = '';

    public string $switchReason = '';

    // Free text shown only when the reason dropdown is set to "Other".
    public string $switchReasonOther = '';

    public ?string $switchError = null;

    public ?string $actionNotice = null;

    public function openSwitch(int $orderProductId): void
    {
        $this->switchingItemId = $orderProductId;
        $this->switchSearch = '';
        $this->switchProduct = '';
        $this->switchPerformedBy = '';
        $this->switchReason = '';
        $this->switchReasonOther = '';
        $this->switchError = null;
        $this->actionNotice = null;

        // Preselect the category of the unit currently assigned — nearly every
        // swap picks an alternate from the SAME category, so this is the
        // logical shortcut. No current unit / no category → no preselection.
        $current = OrderProduct::with('softAssignment.equipment:id,product_category_id')
            ->find($orderProductId)?->softAssignment?->equipment;
        $this->switchCategory = $current?->product_category_id ? (string) $current->product_category_id : '';
    }

    /** Category drives the Product list — clear a now-invalid product pick. */
    public function updatedSwitchCategory(): void
    {
        $this->switchProduct = '';
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

            // Reason = a standard picklist answer, or the free text when
            // "Other" is chosen. Still required for a non-direct swap — the
            // service enforces that (unchanged process, web + mobile).
            $reason = $this->switchReason === 'Other'
                ? (trim($this->switchReasonOther) ?: null)
                : (trim($this->switchReason) ?: null);

            $result = EquipmentReassignmentService::switch(
                orderProduct: $orderProduct,
                replacement: $replacement,
                performedBy: $performedBy,
                actor: auth()->user(),
                source: EquipmentReassignmentService::SOURCE_WEB,
                reason: $reason,
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

    // ── Mark as Staged (admin readiness modal, 2026-07-20; assignment
    //    integration same day) — the modal works for assigned AND unassigned
    //    cards. Queue Line is a primary place the physical unit is confirmed
    //    or changed, so the modal may invoke the CANONICAL assignment
    //    operation (EquipmentReassignmentService via
    //    QueueLineStagingService::assignAndStage); it owns no assignment
    //    state of its own. ─────────────────────────────────────────────────

    public ?int $stagingItemId = null;

    /** 'current' (accept the displayed assignment) | 'assign' (select a
     *  different/first unit through the canonical Category → Equipment
     *  dependency). Forced to 'assign' while the item has no assignment. */
    public string $stagingMode = 'current';

    /** Category → Equipment selection for the 'assign' path — same
     *  canonical dependency the Order Details Assign Equipment modal uses
     *  (equipment.product_category_id). */
    public string $stagingCategory = '';

    public string $stagingEquipmentId = '';

    /** Required by the canonical switch rule when the selected unit is not
     *  a direct match for the ordered product. */
    public string $stagingReason = '';

    /** The soft-assign episode + unit the modal is DISPLAYING — recomputed
     *  every render so the screen and the stale-protection baseline always
     *  agree; the service rejects a submission whose baseline moved on. */
    public ?int $stagingBaselineAssignmentId = null;

    public ?int $stagingBaselineEquipmentId = null;

    /** Deliberately NOT reset between items — shared-terminal employees
     *  stage several machines in a row (the fuel-modal convention). */
    public string $stagingPerformedBy = '';

    /** '' | 'full' | 'not_full' — no preselection: readiness is confirmed
     *  explicitly, matching the existing fuel workflow. */
    public string $stagingFuel = '';

    /** '' | 'with_machine' | 'missing' */
    public string $stagingKey = '';

    public ?string $stagingError = null;

    /** Green thumbs-up read-only status dialog. */
    public ?int $statusItemId = null;

    public function openStaging(int $orderProductId): void
    {
        $this->stagingItemId = $orderProductId;
        $this->stagingMode = 'current'; // render() forces 'assign' when unassigned
        $this->stagingCategory = '';
        $this->stagingEquipmentId = '';
        $this->stagingReason = '';
        $this->stagingFuel = '';
        $this->stagingKey = '';
        $this->stagingError = null;
        $this->actionNotice = null;
    }

    /** Category change resets the dependent Equipment selection. */
    public function updatedStagingCategory(): void
    {
        $this->stagingEquipmentId = '';
    }

    public function closeStaging(): void
    {
        $this->stagingItemId = null;
        $this->stagingError = null;
    }

    public function confirmStaging(): void
    {
        $this->stagingError = null;

        try {
            $orderProduct = OrderProduct::with('softAssignment.equipment', 'order', 'queueLineItem')
                ->findOrFail($this->stagingItemId);

            $performedBy = User::active()->find((int) $this->stagingPerformedBy);
            if (! $performedBy) {
                $this->stagingError = 'Select the employee who staged this machine before confirming.';

                return;
            }

            // The unit being confirmed: the displayed current assignment, or
            // the replacement selected through the Category → Equipment path.
            $targetId = $this->stagingMode === 'assign'
                ? (int) $this->stagingEquipmentId
                : (int) $this->stagingBaselineEquipmentId;

            $target = Equipment::find($targetId);
            if (! $target) {
                $this->stagingError = 'Select the equipment being staged before confirming.';

                return;
            }

            $result = \App\Services\QueueLine\QueueLineStagingService::assignAndStage(
                orderProduct: $orderProduct,
                baselineAssignmentId: $this->stagingBaselineAssignmentId,
                target: $target,
                performedBy: $performedBy,
                actor: auth()->user(),
                fuelFull: $this->stagingFuel === 'full',
                keyWithMachine: $this->stagingKey === 'with_machine',
                reason: trim($this->stagingReason) ?: null,
                source: $this->wallboard ? QueueLineFuelVerification::SOURCE_WALL : QueueLineFuelVerification::SOURCE_WEB,
            );

            $notice = $result['changed']
                ? "{$target->equipment_name} assigned and marked as staged by {$performedBy->full_name} — ready for handoff."
                : "{$target->equipment_name} marked as staged by {$performedBy->full_name} — ready for handoff.";

            if ($result['conflicts']->isNotEmpty()) {
                $notice .= ' Note: this creates ' . $result['conflicts']->count()
                    . ' scheduling conflict(s) — flagged on Schedule Conflicts for admin review.';
            }

            $this->actionNotice = $notice;
            $this->closeStaging();
        } catch (\InvalidArgumentException $e) {
            // QUEUE_ASSIGNMENT_CHANGED included: the next render recomputes
            // the baseline from the live assignment, so the modal refreshes
            // its current-assignment display alongside this message.
            $this->stagingError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->stagingError = 'The staging could not be saved. Please refresh and try again.';
        }
    }

    public function openStagedStatus(int $orderProductId): void
    {
        $this->statusItemId = $orderProductId;
        $this->actionNotice = null;
    }

    public function closeStagedStatus(): void
    {
        $this->statusItemId = null;
    }

    public function returnToPending(int $orderProductId): void
    {
        try {
            $orderProduct = OrderProduct::with('softAssignment.equipment', 'order', 'queueLineItem')
                ->findOrFail($orderProductId);

            \App\Services\QueueLine\QueueLineStagingService::returnToPending($orderProduct, auth()->user());

            $this->actionNotice = 'Returned to Pending — its staging checks must be confirmed again before restaging.';
            $this->closeStagedStatus();
        } catch (\InvalidArgumentException $e) {
            $this->actionError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->actionError = 'The item could not be returned to Pending. Please refresh and try again.';
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

    /** Candidate units for the switch modal — Equipment ID search + dependent Category → Product filters. */
    private function switchCandidates(OrderProduct $item): Collection
    {
        $term = trim($this->switchSearch);
        $categoryId = \App\Helpers\ProductFilterHelper::normalizeCategoryId($this->switchCategory);
        $productId = \App\Helpers\ProductFilterHelper::normalizeProductId($this->switchProduct, $categoryId);

        return Equipment::query()
            ->where('current_status', '!=', 'rented')
            ->when($item->softAssignment?->equipment_id, fn ($q, $current) => $q->where('id', '!=', $current))
            ->when($categoryId, fn ($q, $c) => $q->where('product_category_id', $c))       // same-category shortcut
            ->when($productId, fn ($q, $p) => $q->where('assigned_product_id', $p))         // narrow to a product
            ->when($term !== '', function ($q) use ($term) {                                // search by Equipment ID (or name)
                $q->where(function ($q) use ($term) {
                    $q->where('equipment_id', 'like', "%{$term}%")
                        ->orWhere('equipment_name', 'like', "%{$term}%");
                });
            })
            ->with('assignedProduct:id,product_name')
            ->orderByRaw('equipment_id = ? DESC', [$term])                              // barcode scan → exact id first
            ->orderByRaw('assigned_product_id = ? DESC', [$item->product_id])           // direct matches next
            ->orderBy('equipment_name')
            ->limit(25)
            ->get();
    }

    public function render()
    {
        $error = null;
        $sections = ['pending' => [], 'ready' => [], 'delivered' => []];
        $suppressed = collect();
        $fuelByAssignment = collect();

        try {
            $storeId = $this->store === 'all' ? null : (int) $this->store;
            $categoryId = \App\Helpers\ProductFilterHelper::normalizeCategoryId($this->category);
            $productId = \App\Helpers\ProductFilterHelper::normalizeProductId($this->product, $categoryId);

            // Narrowing filters ride the canonical board query — the same
            // WHERE shapes Schedule uses (transport mode column; category
            // via the product_category_children pivot; ORDERED product_id).
            $boardQuery = QueueLineEligibility::boardQuery($storeId)
                ->when($this->method !== 'all', fn ($q) => $q->where('delivery_transport_mode', $this->method))
                ->when($categoryId, fn ($q) => $q->whereHas('product.categories', fn ($c) => $c->where('product_categories.id', $categoryId)))
                ->when($productId, fn ($q) => $q->where('product_id', $productId));

            $rows = QueueLineEligibility::sortItems(
                QueueLineEligibility::filterFinanciallyActive($boardQuery->get())
            );

            // Time narrowing uses the canonical bucket rule — 'all' keeps
            // the full eligibility window untouched; 'today' (Today Only)
            // drops the tomorrow bucket.
            if ($this->time === 'today') {
                $allowedBuckets = [QueueLineEligibility::BUCKET_OVERDUE, QueueLineEligibility::BUCKET_TODAY];

                $rows = $rows
                    ->filter(fn (OrderProduct $row) => in_array(QueueLineEligibility::bucketFor($row), $allowedBuckets, true))
                    ->values();
            }

            // Payment narrowing — reads the eager-loaded order.lastPayment
            // relation (zero extra queries), the exact field the card badge
            // renders.
            if ($this->payment !== 'all') {
                $rows = $rows->filter(fn (OrderProduct $row) => $this->matchesPaymentFilter($row))->values();
            }

            // Current fuel state for every visible card in ONE query — a row
            // is current only when keyed to the item's LIVE soft-assign
            // episode (matched in the card partial against softAssignment->id).
            // Resolved BEFORE sections because Ready membership depends on it.
            $fuelByAssignment = QueueLineFuelVerification::query()
                ->whereIn('order_product_id', $rows->pluck('id')->all() ?: [0])
                ->where('action', QueueLineFuelVerification::ACTION_VERIFIED)
                ->whereDoesntHave('reversal')
                ->with('performedBy:id,first_name,last_name')
                ->get()
                ->keyBy('equipment_soft_assign_id');

            $keyByAssignment = \App\Services\QueueLine\QueueLineStagingService::keyMap($rows);

            $sections = $this->buildSections($rows, $fuelByAssignment, $keyByAssignment);
            $sections['delivered'] = $this->deliveredToday($storeId, $categoryId, $productId)->all();
            $suppressed = $this->suppressedItems();
        } catch (\Throwable $e) {
            report($e);
            $error = 'The Queue Line board could not be loaded. Please refresh — if this keeps happening, contact support.';
        }

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

        // Mark as Staged modal (gray thumbs-up) — opens for assigned AND
        // unassigned cards; the assignment area inside handles both.
        $stagingItem = null;
        $stagingCategories = collect();
        $stagingEquipmentOptions = collect();
        $stagingSelectedUnit = null;

        if ($this->stagingItemId) {
            $stagingItem = OrderProduct::with('softAssignment.equipment.store', 'product:id,product_name', 'order')
                ->find($this->stagingItemId);

            if ($stagingItem) {
                // Baseline = what this render DISPLAYS. Recomputed every
                // render so screen and stale-protection always agree; the
                // service rejects a submission whose baseline moved on.
                $live = $stagingItem->softAssignment;
                $this->stagingBaselineAssignmentId = $live?->id;
                $this->stagingBaselineEquipmentId = $live?->equipment_id;

                if (! $live) {
                    $this->stagingMode = 'assign';
                }

                $activeEmployees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

                // Canonical Category → Equipment dependency — the same
                // source the Order Details Assign Equipment modal renders
                // (ProductCategory + equipment.product_category_id), loaded
                // lazily per selection instead of as one page-wide JSON blob.
                $stagingCategories = \App\Models\ProductManagement\ProductCategory::orderBy('title')->get(['id', 'title']);

                if ($this->stagingMode === 'assign' && $this->stagingCategory !== '') {
                    $stagingEquipmentOptions = Equipment::with('store:id,store_name')
                        ->where('product_category_id', (int) $this->stagingCategory)
                        ->orderBy('equipment_name')
                        ->get();
                }

                if ($this->stagingMode === 'assign' && $this->stagingEquipmentId !== '') {
                    $stagingSelectedUnit = Equipment::with('store:id,store_name')->find((int) $this->stagingEquipmentId);
                }
            } else {
                $this->stagingItemId = null;
            }
        }

        // Staged status dialog (green thumbs-up)
        $statusItem = null;
        $statusFuel = null;
        $statusKey = null;

        if ($this->statusItemId) {
            $statusItem = OrderProduct::with('softAssignment.equipment', 'product:id,product_name', 'order', 'queueLineItem')
                ->find($this->statusItemId);

            if ($statusItem) {
                $statusFuel = QueueFuelVerificationService::currentVerification($statusItem);
                $statusKey = \App\Services\QueueLine\QueueLineStagingService::currentKey($statusItem);
                $statusFuel?->load('performedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name');
                $statusKey?->load('performedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name');
            } else {
                $this->statusItemId = null;
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

        $switchCategoryOptions = [];
        $switchProductOptions = collect();

        if ($this->switchingItemId) {
            $switchingItem = OrderProduct::with('softAssignment.equipment', 'product:id,product_name', 'order')
                ->find($this->switchingItemId);

            if ($switchingItem) {
                $switchCandidates = $this->switchCandidates($switchingItem);
                $activeEmployees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

                // Dependent Category → Product options for the picker (same
                // canonical sources as the board filter bar), reduced to the
                // preselected/chosen category.
                $switchCategoryOptions = \App\Models\ProductManagement\ProductCategory::getHierarchy();
                $switchProductOptions = \App\Helpers\ProductFilterHelper::productOptions();
                $normalizedSwitchCategory = \App\Helpers\ProductFilterHelper::normalizeCategoryId($this->switchCategory);
                if ($normalizedSwitchCategory !== null) {
                    $switchMemberIds = \App\Helpers\ProductFilterHelper::categoryProductMap()[$normalizedSwitchCategory] ?? [];
                    $switchProductOptions = $switchProductOptions->only($switchMemberIds);
                }
            } else {
                $this->switchingItemId = null;
            }
        }

        // Dependent Category → Product options — the same canonical sources
        // the Orders and Schedule pages use (ProductFilterHelper +
        // ProductCategory::getHierarchy). With no category selected the
        // Product list is the full lineup; with one selected it reduces to
        // that category's pivot membership. Wall-board mode renders no
        // filter bar, so it skips the option queries entirely.
        $categoryOptions = [];
        $productOptions = collect();

        if (! $this->wallboard) {
            $categoryOptions = \App\Models\ProductManagement\ProductCategory::getHierarchy();
            $productOptions = \App\Helpers\ProductFilterHelper::productOptions();

            $normalizedCategory = \App\Helpers\ProductFilterHelper::normalizeCategoryId($this->category);
            if ($normalizedCategory !== null) {
                $memberIds = \App\Helpers\ProductFilterHelper::categoryProductMap()[$normalizedCategory] ?? [];
                $productOptions = $productOptions->only($memberIds);
            }
        }

        return view('livewire.queue-line.board', [
            'sections' => $sections,
            'suppressedItems' => $suppressed,
            'categoryOptions' => $categoryOptions,
            'productOptions' => $productOptions,
            'stores' => Store::active()->orderBy('store_name')->get(['id', 'store_name']),
            'boardError' => $error,
            'lastUpdated' => now(), // re-stamped by every poll/action render
            'switchingItem' => $switchingItem,
            'switchCandidates' => $switchCandidates,
            'switchCategoryOptions' => $switchCategoryOptions,
            'switchProductOptions' => $switchProductOptions,
            'activeEmployees' => $activeEmployees,
            'fuelByAssignment' => $fuelByAssignment,
            'fuelItem' => $fuelItem,
            'fuelCurrent' => $fuelCurrent,
            'fuelHistory' => $fuelHistory,
            'stagingItem' => $stagingItem,
            'stagingCategories' => $stagingCategories,
            'stagingEquipmentOptions' => $stagingEquipmentOptions,
            'stagingSelectedUnit' => $stagingSelectedUnit,
            'statusItem' => $statusItem,
            'statusFuel' => $statusFuel,
            'statusKey' => $statusKey,
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
     * @param  array<string, array<int, OrderProduct>>  $sections  pending/ready/delivered flat card lists
     * @param  Collection  $fuelByAssignment  current verifications keyed by soft-assign episode id
     * @return array<string, int>
     */
    private function summarize(array $sections, Collection $fuelByAssignment): array
    {
        $summary = [
            'cards' => 0,
            'pending' => count($sections['pending'] ?? []),
            'ready' => count($sections['ready'] ?? []),
            'delivered' => count($sections['delivered'] ?? []),
            'rush' => 0, 'overdue' => 0, 'today' => 0, 'tomorrow' => 0,
            'needsEquipment' => 0,
            'fuelNotVerified' => 0,
            'alternate' => 0,
            'unknown' => 0,
            'maintenanceHold' => 0,
            'damaged' => 0,
        ];

        foreach (['pending', 'ready'] as $sectionKey) {
            foreach ($sections[$sectionKey] ?? [] as $item) {
                $summary['cards']++;

                if ($item->queueLineItem?->isRushed()) {
                    $summary['rush']++;
                }
                $summary[QueueLineEligibility::bucketFor($item)]++;

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

                // Applicability (2026-07-21): only fuel-burning units can be
                // "fuel not verified" — attachments/electric never count.
                if ($item->softAssignment->equipment?->requiresFuelCheck()
                    && ! isset($fuelByAssignment[$item->softAssignment->id])) {
                    $summary['fuelNotVerified']++;
                }

                $equipment = $item->softAssignment->equipment;

                if ($equipment?->current_status?->value === 'maintenance') {
                    $summary['maintenanceHold']++;
                }

                // Equipment status only — Rental Ready inspection state is
                // deliberately not surfaced on Queue Line (refinement
                // 2026-07-20), so the tile mirrors exactly what cards show.
                if ($equipment?->current_status?->value === 'damaged') {
                    $summary['damaged']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Workflow sections (admin readiness modal, 2026-07-20):
     *   Pending = staging not complete — no machine, or the thumbs-up
     *             staging (fuel + key + staged latch) hasn't happened for
     *             the live assignment episode.
     *   Staged  = the Mark as Staged action completed: staged latch set AND
     *             current fuel verification AND current key confirmation —
     *             ready to hand to the driver or in-store customer.
     * Derived, never stored: switching equipment starts a new episode, so a
     * staged card automatically falls back to Pending.
     * Urgency (RUSH/Overdue/Today/Tomorrow) stays visible as card badges and
     * still drives ordering INSIDE each section (sortItems runs first).
     *
     * @return array{pending: array<int, OrderProduct>, ready: array<int, OrderProduct>}
     */
    private function buildSections(Collection $rows, Collection $fuelByAssignment, Collection $keyByAssignment): array
    {
        $sections = ['pending' => [], 'ready' => []];

        foreach ($rows as $row) {
            $assignmentId = $row->softAssignment?->id;

            $fullyStaged = \App\Services\QueueLine\QueueLineStagingService::isFullyStaged(
                $row,
                $assignmentId ? ($fuelByAssignment[$assignmentId] ?? null) : null,
                $assignmentId ? ($keyByAssignment[$assignmentId] ?? null) : null,
            );

            $sections[$fullyStaged ? 'ready' : 'pending'][] = $row;
        }

        return $sections;
    }

    /** 'paid' keeps the "Paid in Full" badge cards; 'pending' keeps the rest. */
    private function matchesPaymentFilter(OrderProduct $row): bool
    {
        $isPaid = $row->order?->last_payment_status === \App\Enums\Orders\OrderPaymentStatus::Paid->value;

        return $isPaid === ($this->payment === 'paid');
    }

    /**
     * Delivered Today — items whose equipment physically left the yard today
     * (QueueLineService::complete latch). Read-only reference cards: the
     * board query excludes completed items, so this is the one extra feed.
     * Returned as OrderProducts with their queueLineItem relation pre-set so
     * the single card partial renders them like any other item.
     */
    private function deliveredToday(?int $storeId, ?int $categoryId = null, ?int $productId = null): Collection
    {
        return QueueLineItem::query()
            ->whereDate('completed_at', today())
            ->whereHas('orderProduct', function ($q) use ($storeId, $categoryId, $productId) {
                $q->when($storeId, fn ($q) => $q->where('delivery_store_id', $storeId))
                    // Delivered Today obeys the same narrowing filters as the
                    // active sections (method/category/product); the Time
                    // filter is inherently satisfied — the section is
                    // today-scoped by definition.
                    ->when($this->method !== 'all', fn ($q) => $q->where('delivery_transport_mode', $this->method))
                    ->when($categoryId, fn ($q) => $q->whereHas('product.categories', fn ($c) => $c->where('product_categories.id', $categoryId)))
                    ->when($productId, fn ($q) => $q->where('product_id', $productId));
            })
            ->whereHas('order')
            ->with([
                'orderProduct.product.mediaChildren',
                'orderProduct.equipment.assignedProduct.mediaChildren',
                'orderProduct.equipment.store:id,store_name',
                'orderProduct.deliveryStore:id,unique_id,store_name',
                'orderProduct.softAssignment.equipment.assignedProduct',
                'order.lastPayment',
            ])
            ->orderByDesc('completed_at')
            ->get()
            ->filter(fn (QueueLineItem $item) => $item->orderProduct !== null && $item->order !== null)
            ->map(function (QueueLineItem $item) {
                return $item->orderProduct
                    ->setRelation('queueLineItem', $item)
                    ->setRelation('order', $item->order);
            })
            // The Completed segment obeys the payment toggle like the
            // active segments (same badge field, same partition)
            ->when($this->payment !== 'all', fn ($items) => $items->filter(
                fn (OrderProduct $row) => $this->matchesPaymentFilter($row)
            ))
            ->values();
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
