@php
    /**
     * Mark as Staged — one atomic readiness action, now assignment-capable
     * (corrective mission 2026-07-20). Queue Line is a primary place the
     * physical unit is confirmed, selected, or changed, so the modal carries
     * the CANONICAL assignment operation inside it:
     *
     *   1. Order context (Order ID / Customer / Product ordered)
     *   2. Equipment assignment — accept the current unit, or select a
     *      different/first one through the same Category → Equipment
     *      dependency the Order Details Assign Equipment modal uses
     *   3. Staging checklist (Staged By / Fuel / Key)
     *
     * Submission runs QueueLineStagingService::assignAndStage — canonical
     * assignment (EquipmentReassignmentService) + fuel + key + staged latch
     * in ONE transaction. A stale screen is rejected, never re-pointed; the
     * baseline recomputes each render so the display refreshes with it.
     */
    $baselineUnit = $stagingItem->softAssignment?->equipment;

    $statusLabel = function ($unit) {
        return match ($unit?->current_status?->value) {
            'maintenance' => 'Maintenance Hold',
            'damaged' => 'Damaged',
            'rented' => 'Rented',
            default => 'Available',
        };
    };

    $statusTone = function ($unit) {
        return match ($unit?->current_status?->value) {
            'maintenance' => 'bg-amber-100 text-amber-900 border-amber-300',
            'damaged' => 'bg-red-100 text-red-900 border-red-300',
            'rented' => 'bg-gray-100 text-gray-600 border-gray-200',
            default => 'bg-green-50 text-green-700 border-green-200',
        };
    };

    $wrongLocation = function ($unit) use ($stagingItem) {
        return $unit?->store_id && $stagingItem->delivery_store_id
            && (int) $unit->store_id !== (int) $stagingItem->delivery_store_id;
    };

    // Same status grouping the canonical Assign Equipment modal renders —
    // nothing is silently hidden; rented units show but cannot be chosen
    // (the canonical assignment rejects physically-rented units at submit).
    $optionGroups = collect($stagingEquipmentOptions)->groupBy(
        fn ($unit) => $statusLabel($unit)
    )->sortBy(fn ($units, $label) => array_search($label, ['Available', 'Maintenance Hold', 'Damaged', 'Rented']));

    // A unit that is not a direct match for the ordered product needs a
    // stated reason (canonical switch rule — the audit trail must say WHY
    // a different product is leaving the yard).
    $reasonRequired = $stagingMode === 'assign'
        && $stagingSelectedUnit
        && (int) $stagingSelectedUnit->assigned_product_id !== (int) $stagingItem->product_id;

    $assignmentResolved = $stagingMode === 'current'
        ? $baselineUnit !== null
        : ($stagingSelectedUnit !== null
            && $stagingSelectedUnit->current_status?->value !== 'rented'
            && (! $reasonRequired || trim($stagingReason) !== ''));

    $stagedUnit = $stagingMode === 'assign' ? $stagingSelectedUnit : $baselineUnit;

    // Applicability (2026-07-21): each check renders ONLY where the physical
    // trait exists on the unit being staged — fuel for explicit Diesel/Gas,
    // key for explicit 1 Key / 2 Keys. A plain attachment shows neither and
    // stages on the employee sign-off alone.
    $needsFuel = $stagedUnit?->requiresFuelCheck() ?? false;
    $needsKey = $stagedUnit?->requiresKeyCheck() ?? false;

    $canSubmit = $assignmentResolved
        && $stagingPerformedBy !== ''
        && (! $needsFuel || $stagingFuel === 'full')
        && (! $needsKey || $stagingKey === 'with_machine');
@endphp
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="staging-modal-{{ $stagingItem->id }}" data-staging-modal>
    <div class="bg-white rounded-lg w-full max-w-xl shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Mark as Staged</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    ID: {{ $stagingItem->order->order_number }} — {{ $stagingItem->product_name }}
                </p>
            </div>
            <button type="button" wire:click="closeStaging"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4 flex flex-col gap-4">
            @if ($stagingError)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $stagingError }}
                </div>
            @endif

            {{-- ── 1 · Order context ─────────────────────────────────── --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 space-y-1 text-sm">
                <p><span class="text-xs font-semibold text-blue-700">Order ID:</span>
                    <span class="font-semibold text-blue-900">#{{ $stagingItem->order->order_number }}</span></p>
                <p><span class="text-xs font-semibold text-blue-700">Customer:</span>
                    <span class="font-semibold text-blue-900">{{ $stagingItem->order->customer_name }}</span></p>
                <p><span class="text-xs font-semibold text-blue-700">Product Ordered:</span>
                    <span class="font-semibold text-blue-900">{{ $stagingItem->product_name }}</span></p>
            </div>

            {{-- ── 2 · Equipment assignment ──────────────────────────── --}}
            @if ($baselineUnit)
                {{-- Use Currently Assigned --}}
                <div class="rounded-lg border {{ $stagingMode === 'current' ? 'border-green-300 bg-green-50' : 'border-gray-200' }} p-3">
                    <label class="flex items-center gap-2 text-sm font-semibold {{ $stagingMode === 'current' ? 'text-green-700' : 'text-gray-700' }}">
                        <input type="radio" wire:model.live="stagingMode" value="current" class="w-4 h-4" data-staging-mode-current />
                        Use Currently Assigned
                    </label>
                    <div class="ml-6 mt-1 text-sm">
                        <p class="font-bold text-gray-900">
                            {{ $baselineUnit->equipment_name }}
                            <span class="font-normal text-gray-500">· #{{ $baselineUnit->equipment_id }}</span>
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded border text-xs font-semibold {{ $statusTone($baselineUnit) }}">{{ $statusLabel($baselineUnit) }}</span>
                            @if ($baselineUnit->store?->store_name)
                                <span class="text-xs text-gray-500">at {{ $baselineUnit->store->store_name }}</span>
                            @endif
                            @if ($wrongLocation($baselineUnit))
                                <span class="px-1.5 py-0.5 rounded border text-xs font-semibold bg-orange-100 text-orange-900 border-orange-300">Wrong Location</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-lg border {{ $stagingMode === 'assign' ? 'border-sky-300 bg-sky-50/50' : 'border-gray-200' }} p-3">
                @if ($baselineUnit)
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="radio" wire:model.live="stagingMode" value="assign" class="w-4 h-4" data-staging-mode-assign />
                        Assign Different Equipment
                    </label>
                @else
                    <p class="text-sm font-semibold text-gray-700">Assign Equipment</p>
                    <p class="text-xs text-amber-700 mt-0.5">No equipment is assigned to this order yet — select the machine being staged.</p>
                @endif

                @if ($stagingMode === 'assign')
                    <div class="{{ $baselineUnit ? 'ml-6' : '' }} mt-2 space-y-2">
                        <div>
                            <label for="staging-category" class="text-sm font-medium text-gray-700 required">Category</label>
                            <select id="staging-category" wire:model.live="stagingCategory" data-staging-category
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                <option value="">Select Category</option>
                                @foreach ($stagingCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="staging-equipment" class="text-sm font-medium text-gray-700 required">Equipment</label>
                            <select id="staging-equipment" wire:model.live="stagingEquipmentId" data-staging-equipment
                                @disabled($stagingCategory === '')
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">{{ $stagingCategory === '' ? 'Select a category first' : 'Select Equipment' }}</option>
                                @foreach ($optionGroups as $groupLabel => $units)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}" @disabled($unit->current_status?->value === 'rented')>
                                                {{ $unit->equipment_name }} · #{{ $unit->equipment_id }}{{ $unit->store?->store_name ? ' — ' . $unit->store->store_name : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                                @if ($stagingCategory !== '' && $optionGroups->isEmpty())
                                    <option value="" disabled>No equipment in this category</option>
                                @endif
                            </select>
                        </div>

                        @if ($stagingSelectedUnit)
                            <div class="flex flex-wrap items-center gap-1.5 text-xs" data-staging-selected-info>
                                <span class="px-1.5 py-0.5 rounded border font-semibold {{ $statusTone($stagingSelectedUnit) }}">{{ $statusLabel($stagingSelectedUnit) }}</span>
                                @if ($stagingSelectedUnit->store?->store_name)
                                    <span class="text-gray-500">at {{ $stagingSelectedUnit->store->store_name }}</span>
                                @endif
                                @if ($wrongLocation($stagingSelectedUnit))
                                    <span class="px-1.5 py-0.5 rounded border font-semibold bg-orange-100 text-orange-900 border-orange-300">Wrong Location</span>
                                @endif
                            </div>
                        @endif

                        @if ($reasonRequired)
                            <div>
                                <label for="staging-reason" class="text-sm font-medium text-gray-700 required">Reason</label>
                                <input type="text" id="staging-reason" wire:model.live="stagingReason" data-staging-reason
                                    placeholder="Why is a different product being staged for this order?"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" />
                                <p class="text-xs text-gray-400 mt-1">
                                    This unit is not a direct match for the ordered product — a short reason is recorded in the order history.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── 3 · Staging checklist ─────────────────────────────── --}}
            @if ($stagedUnit)
                <div class="rounded-lg border border-green-300 bg-green-50 p-3">
                    <p class="text-xs uppercase tracking-wide font-semibold text-green-700">Equipment being staged</p>
                    <p class="text-base font-bold text-gray-900 mt-0.5">
                        {{ $stagedUnit->equipment_name }}
                        <span class="font-normal text-gray-500">· #{{ $stagedUnit->equipment_id }}</span>
                    </p>
                </div>
            @endif

            {{-- Who physically staged it (shared terminals — self-selected) --}}
            <div>
                <label for="staging-performed-by" class="text-sm font-medium text-gray-700 required">Staged By</label>
                <select id="staging-performed-by" wire:model.live="stagingPerformedBy"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                    <option value="">Select the employee who staged this machine…</option>
                    @foreach ($activeEmployees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    Entered by {{ auth()->user()->full_name }} — the staging is attributed to the employee selected above.
                </p>
            </div>

            @if ($needsFuel || $needsKey)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Fuel — Diesel/Gas units only --}}
                    @if ($needsFuel)
                        <fieldset class="rounded-lg border border-gray-200 p-3" data-staging-fuel-check>
                            <legend class="text-sm font-medium text-gray-700 px-1 required">Fuel</legend>
                            <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                                <input type="radio" wire:model.live="stagingFuel" value="full" class="w-4 h-4" />
                                Full
                            </label>
                            <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                                <input type="radio" wire:model.live="stagingFuel" value="not_full" class="w-4 h-4" />
                                Not Full
                            </label>
                        </fieldset>
                    @endif

                    {{-- Key — 1 Key / 2 Keys starting mechanisms only --}}
                    @if ($needsKey)
                        <fieldset class="rounded-lg border border-gray-200 p-3" data-staging-key-check>
                            <legend class="text-sm font-medium text-gray-700 px-1 required">Key</legend>
                            <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                                <input type="radio" wire:model.live="stagingKey" value="with_machine" class="w-4 h-4" />
                                With Machine
                            </label>
                            <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                                <input type="radio" wire:model.live="stagingKey" value="missing" class="w-4 h-4" />
                                Missing
                            </label>
                        </fieldset>
                    @endif
                </div>
            @elseif ($stagedUnit)
                <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-3 py-2" data-staging-no-checks>
                    No fuel or key check applies to this equipment — staging confirms it is pulled and ready for handoff.
                </p>
            @endif

            @if (($needsFuel && $stagingFuel === 'not_full') || ($needsKey && $stagingKey === 'missing'))
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    Staging records COMPLETE readiness — resolve the fuel or key issue first, then mark the machine as staged.
                </p>
            @endif

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeStaging"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Cancel
                </button>
                <button type="button" wire:click="confirmStaging"
                    wire:loading.attr="disabled" @disabled(! $canSubmit)
                    data-staging-submit
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500">
                    Mark as Staged
                </button>
            </div>
        </div>
    </div>
</div>
