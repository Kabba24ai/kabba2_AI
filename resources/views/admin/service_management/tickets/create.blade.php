@extends('admin.layouts.app')

@section('title', 'New Service Ticket')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Service\ServicePriority;
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
        $selectedPersonnel = collect(old('personnel', []))->map(fn ($v) => (int) $v)->all();
    @endphp

    {{-- ===== Header ===== --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    <x-heroicon-o-plus-circle class="w-6 h-6 text-blue-600" />
                    New Service Ticket
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">Intake Stage</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Start the service intake. Diagnosis, approval, parts, labor, and final resolution continue on the ticket workbench.
            </p>
        </div>
        <a href="{{ route('admin.service-management.tickets.index') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Tickets
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-700 mb-1">Please correct the following:</p>
            <ul class="text-sm text-red-600 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.service-management.tickets.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="intake" value="1">
        {{-- ST-1 idempotency: one token per form render, preserved across a
             validation round-trip via old() so a double-submit (or a
             resubmit after a validation error) dedupes to the first ticket. --}}
        <input type="hidden" name="idempotency_token" value="{{ old('idempotency_token', (string) Str::uuid()) }}">

        @php $ticketSource = old('ticket_source', 'customer'); @endphp

        {{-- Containerized single-column intake: the summary/next-steps
             sidebar was removed (no operational value) — the form itself is
             the whole page, centered at a readable width. --}}
        <div class="max-w-4xl mx-auto">

            <div class="space-y-6">

                {{-- ===== Step 1: Ticket Source ===== --}}
                {{-- One shared intake, two paths. The choice here decides which
                     fields render below; switching safely clears the other
                     path's state (handled in JS) so nothing stale is posted. --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                        </span>
                        Ticket Source
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                        <label class="st-source-card relative flex flex-col gap-1 rounded-lg border p-4 cursor-pointer transition"
                               data-source="customer">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="ticket_source" value="customer" class="st-source-radio text-blue-600 focus:ring-blue-500"
                                    @checked($ticketSource === 'customer')>
                                <span class="text-sm font-semibold text-gray-800">Customer-Related Ticket</span>
                            </span>
                            <span class="text-xs text-gray-500 pl-6">Equipment issue associated with a customer rental order.</span>
                        </label>
                        <label class="st-source-card relative flex flex-col gap-1 rounded-lg border p-4 cursor-pointer transition"
                               data-source="standard">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="ticket_source" value="standard" class="st-source-radio text-blue-600 focus:ring-blue-500"
                                    @checked($ticketSource === 'standard')>
                                <span class="text-sm font-semibold text-gray-800">Standard Equipment Ticket</span>
                            </span>
                            <span class="text-xs text-gray-500 pl-6">Equipment issue for a fleet unit that is not associated with a customer order.</span>
                        </label>
                    </div>
                </div>

                {{-- ===== Card 1: Rental Order Source (customer path only) ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5" data-src="customer">
                    <div class="flex items-center justify-between gap-4 mb-1">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <x-heroicon-o-shopping-cart class="w-4 h-4" />
                            </span>
                            Rental Order Source
                        </h2>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">
                        Find the rental order by order # or by customer name — category, product, and rental date all come
                        from the order itself.
                    </p>

                    {{-- Two search windows (same pattern as Orders): either one
                         resolves to the ONE canonical order selection below. --}}
                    <div id="st-order-pickers" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}" for="st-search-order">Search by Order #</label>
                            <div class="relative">
                                <input type="text" id="st-search-order" autocomplete="off" placeholder="e.g. 3151"
                                       class="{{ $inputClass }}">
                                <div id="st-search-order-results"
                                     class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="st-search-customer">Search by Customer</label>
                            <div class="relative">
                                <input type="text" id="st-search-customer" autocomplete="off" placeholder="Customer name…"
                                       class="{{ $inputClass }}">
                                <div id="st-search-customer-results"
                                     class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Selected order chip --}}
                    <div id="st-order-selected" class="hidden items-center justify-between gap-3 border border-blue-200 bg-blue-50/60 rounded-md px-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <span id="st-order-selected-label" class="font-medium text-gray-800"></span>
                            <p id="st-order-selected-date" class="text-xs text-gray-500 mt-0.5"></p>
                        </div>
                        <button type="button" id="st-order-change" class="text-xs text-blue-600 hover:underline shrink-0">Change</button>
                    </div>

                    {{-- The ONE canonical order control — posts order_id and drives
                         equipment/complaints exactly as before; the two search
                         windows above are its only UI. --}}
                    <select name="order_id" id="st-order" class="hidden" aria-hidden="true" tabindex="-1">
                        <option value="">Search by order # or customer…</option>
                        @foreach ($orderOptions as $order)
                            <option value="{{ $order['id'] }}" @selected((int) old('order_id') === $order['id'])>{{ $order['label'] }}</option>
                        @endforeach
                    </select>
                    <p id="st-order-required" class="hidden text-sm text-red-600 mt-2">Select the rental order first.</p>
                    @error('order_id')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                </div>

                {{-- ===== Card 2: Equipment & Service Location ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                        </span>
                        Equipment &amp; Service Location
                    </h2>
                    <div class="mb-4"></div>

                    {{-- Customer path: equipment comes from the selected order,
                         with an optional override for the wrong-unit case. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-src="customer">
                        <div>
                            <label class="{{ $labelClass }} required">Equipment</label>
                            <select name="equipment_id" id="st-equipment" required disabled class="{{ $inputClass }}"
                                data-old="{{ old('ticket_source', 'customer') === 'customer' ? old('equipment_id') : '' }}">
                                <option value="">Select an order first…</option>
                            </select>
                            {{-- Single-equipment orders lock the select (nothing to choose);
                                 a disabled select never posts, so this mirror carries the value --}}
                            <input type="hidden" name="equipment_id" id="st-equipment-locked" value="" disabled>
                            @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            {{-- Equipment ID Override: the machine actually being repaired
                                 when the order carries the wrong unit. The rental order is
                                 never modified — both references are preserved. --}}
                            <label class="{{ $labelClass }}" for="st-override">Equipment ID Override</label>
                            <select name="equipment_override_id" id="st-override" class="{{ $inputClass }}">
                                <option value="">No override</option>
                                @foreach ($overrideEquipment as $unit)
                                    <option value="{{ $unit['id'] }}" @selected((int) old('equipment_override_id') === $unit['id'])>{{ $unit['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Only when the unit being repaired differs from the order.</p>
                        </div>
                    </div>

                    {{-- Standard path: pick a category, then the fleet unit within
                         it. Equipment is fetched server-side per category (never
                         the whole fleet) and matches Name + Equipment ID only. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-src="standard">
                        <div>
                            <label class="{{ $labelClass }} required">Equipment Category</label>
                            <select name="equipment_category_id" id="st-category" class="{{ $inputClass }}">
                                <option value="">Select a category…</option>
                                @foreach ($equipmentCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((int) old('equipment_category_id') === $cat->id)>{{ $cat->title }}</option>
                                @endforeach
                            </select>
                            @error('equipment_category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }} required" for="st-std-search">Equipment</label>
                            <div class="relative">
                                <input type="text" id="st-std-search" autocomplete="off" disabled
                                       placeholder="Select a category first…" class="{{ $inputClass }}">
                                <div id="st-std-results"
                                     class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                            </div>
                            {{-- The one posted equipment id for the standard path. Enabled
                                 only while this path is active, so it never collides with
                                 the customer equipment control (same name). --}}
                            <input type="hidden" name="equipment_id" id="st-std-equipment" value="" disabled>
                            {{-- Selected-unit chip (name + equipment id) --}}
                            <div id="st-std-selected" class="hidden items-center justify-between gap-3 border border-blue-200 bg-blue-50/60 rounded-md px-3 py-2 mt-2 text-sm">
                                <span id="st-std-selected-label" class="font-medium text-gray-800 min-w-0 truncate"></span>
                                <button type="button" id="st-std-change" class="text-xs text-blue-600 hover:underline shrink-0">Change</button>
                            </div>
                            <p id="st-std-required" class="hidden text-sm text-red-600 mt-1">Select an equipment category and unit.</p>
                            <p class="text-xs text-gray-400 mt-1">Search by equipment name or ID within the category.</p>
                        </div>
                    </div>

                    {{-- Shared: service store + priority apply to both paths --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="{{ $labelClass }}" id="st-store-label">Service Store</label>
                            <select name="service_store_id" class="{{ $inputClass }}">
                                <option value="">— Select store —</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected((int) old('service_store_id') === $store->id)>{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Where the equipment will be repaired.</p>
                            @error('service_store_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }} required">Priority</label>
                            <select name="priority" required class="{{ $inputClass }}">
                                @foreach (ServicePriority::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('priority', 'normal') === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Override Reason: customer path only, once an override is chosen --}}
                    <div class="mt-4 {{ old('equipment_override_id') ? '' : 'hidden' }}" id="st-override-reason-wrap" data-src="customer">
                        <label class="{{ $labelClass }}">Override Reason</label>
                        <input type="text" name="equipment_override_reason" value="{{ old('equipment_override_reason') }}"
                            class="{{ $inputClass }}" maxlength="255"
                            placeholder="Explain why the equipment ID is being overridden…">
                        <p class="text-xs text-gray-400 mt-1">Optional — e.g. wrong unit assigned, customer exchanged machines, yard loaded incorrect unit.</p>
                    </div>
                </div>

                {{-- ===== Card 3: Assigned Personnel ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 text-gray-400 flex items-center justify-center shrink-0">
                                <x-heroicon-o-user-group class="w-5 h-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-gray-800">Assigned Personnel</h2>
                                <p class="text-xs text-gray-400 truncate" id="st-crew-summary">No one assigned yet.</p>
                            </div>
                        </div>
                        <button type="button" id="st-assign-toggle"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-sm font-medium text-blue-700 hover:bg-blue-100 transition shrink-0">
                            <x-heroicon-o-user-plus class="w-4 h-4" />
                            Assign Now
                        </button>
                    </div>

                    <div id="st-assign-panel" class="hidden mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs text-gray-500 mb-3">
                            Check everyone working this ticket, then mark one person as <span class="font-semibold">Team Leader</span>.
                            Employees come from HRM records.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($employees as $employee)
                                <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                                    <label class="flex items-center gap-2 cursor-pointer min-w-0">
                                        <input type="checkbox" name="personnel[]" value="{{ $employee->id }}"
                                            class="st-crew-check text-blue-600 rounded focus:ring-blue-500"
                                            data-name="{{ $employee->first_name }} {{ $employee->last_name }}"
                                            @checked(in_array($employee->id, $selectedPersonnel, true))>
                                        <span class="text-sm text-gray-700 truncate">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    </label>
                                    <label class="flex items-center gap-1 cursor-pointer shrink-0" title="Team Leader">
                                        <input type="radio" name="team_leader_id" value="{{ $employee->id }}"
                                            class="st-leader-radio text-amber-500 focus:ring-amber-400"
                                            @checked((int) old('team_leader_id') === $employee->id)
                                            @disabled(!in_array($employee->id, $selectedPersonnel, true))>
                                        <span class="text-[11px] font-semibold text-amber-600 uppercase">Lead</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('team_leader_id')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        @error('personnel')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                    </div>

                    {{-- Assigned team at a glance — avatar chips in the same style as
                         partials/_personnel_avatars. Display only; the checkboxes above
                         remain the editing controls and the JS keeps this in sync. --}}
                    <div id="st-crew-display" class="hidden mt-4 pt-4 border-t border-gray-100">
                        <div id="st-crew-lead-wrap" class="hidden mb-3">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Lead Technician</p>
                            <div id="st-crew-lead" class="flex flex-wrap gap-2"></div>
                        </div>
                        <div id="st-crew-team-wrap" class="hidden">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Assigned Team</p>
                            <div id="st-crew-team" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>

                {{-- ===== Card 4: Reported Problem — structured complaint intake ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </span>
                        Reported Problem
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">
                        What is wrong with the machine — not how it will be fixed. Check every reported complaint;
                        diagnosis, causes, and repairs happen on the workbench.
                    </p>

                    {{-- Applicable complaints — rendered client-side from the library,
                         filtered by the selected equipment's product, categories, and
                         recorded capabilities --}}
                    <div id="st-complaint-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center">
                        <p class="text-sm text-gray-400">Select equipment to see the applicable complaints.</p>
                    </div>
                    {{-- Shown when the selected unit has no Reported-Problem
                         template (and no legacy profile match). The full
                         library still renders below as a working fallback so
                         intake is never blocked before templates are attached. --}}
                    <div id="st-complaint-no-template" class="hidden mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5">
                        <p class="text-sm text-amber-800"><span class="font-semibold">No Template Listed</span> for this unit — showing all symptoms. Attach a template on the equipment record for a focused list.</p>
                    </div>
                    <div id="st-complaint-list" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                    @error('complaints')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                    @error('complaints.*')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror

                    {{-- Selected complaints as removable chips --}}
                    <div id="st-complaint-chips-wrap" class="hidden mt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Selected Complaints</p>
                        <div id="st-complaint-chips" class="flex flex-wrap gap-2"></div>
                    </div>

                    <div class="mt-4">
                        <label class="{{ $labelClass }}">Complaint Details</label>
                        <textarea name="customer_complaint" rows="4" class="{{ $inputClass }}"
                            placeholder="Describe what the customer or employee observed, when it happens, warning codes, noises, or additional details not covered by the selected complaints.">{{ old('customer_complaint') }}</textarea>
                    </div>

                    {{-- Complaint evidence — photos & video, uploaded with the ticket --}}
                    <div class="mt-4">
                        <label class="{{ $labelClass }}">Complaint Evidence</label>
                        <div id="st-evidence-drop"
                            class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center transition">
                            <p class="text-sm text-gray-500">Drag &amp; drop photos or videos here, or</p>
                            <div class="flex items-center justify-center gap-2 mt-2">
                                <button type="button" id="st-evidence-photo-btn"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 hover:bg-gray-100 transition">
                                    <x-heroicon-o-camera class="w-4 h-4" />
                                    Upload Photos
                                </button>
                                <button type="button" id="st-evidence-video-btn"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 hover:bg-gray-100 transition">
                                    <x-heroicon-o-video-camera class="w-4 h-4" />
                                    Upload Video
                                </button>
                            </div>
                            <input type="file" name="evidence[]" id="st-evidence-photos" class="hidden"
                                accept="image/*" capture="environment" multiple>
                            <input type="file" name="evidence[]" id="st-evidence-videos" class="hidden"
                                accept="video/*" multiple>
                        </div>
                        <div id="st-evidence-previews" class="hidden mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
                        @error('evidence.*')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="{{ $labelClass }}">Notes</label>
                        <textarea name="internal_notes" rows="3" class="{{ $inputClass }}"
                            placeholder="Internal notes (not customer-facing)…">{{ old('internal_notes') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-3">
                    <a href="{{ route('admin.service-management.tickets.index') }}"
                        class="px-6 py-3 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                        Create Ticket
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Order → equipment map: this path never offers unrelated equipment
    const ORDERS = @json($orderOptions);

    // ── Intake source (customer | standard) ────────────────────────────
    // One shared form, two paths. `currentSource` drives which equipment
    // control feeds the shared complaint engine; `standardUnit` is the unit
    // chosen on the standard path (shape-compatible with an order unit so the
    // exact same symptom resolution applies).
    let currentSource = @json($ticketSource);
    let standardUnit  = @json($oldStandardEquipment);
    const EQUIPMENT_SEARCH_URL = @json(route('admin.service-management.tickets.equipment-search'));

    // Customer path: the order the employee picked from the LIVE server search.
    // It (not the preloaded ORDERS list) is the source of truth for the chosen
    // order's equipment + complaint resolution, so orders outside any preload —
    // e.g. soft-assigned Queue Line orders — work identically once selected.
    const ORDER_SEARCH_URL = @json(route('admin.service-management.tickets.order-search'));
    let selectedCustomerOrder = @json($oldCustomerOrder);

    const orderSelect     = document.getElementById('st-order');
    const equipmentSelect = document.getElementById('st-equipment');
    const equipmentLocked = document.getElementById('st-equipment-locked');

    // Resolve the currently-selected customer order: the live-searched
    // selection wins; ORDERS remains only a fallback.
    function currentOrder() {
        if (selectedCustomerOrder && String(selectedCustomerOrder.id) === String(orderSelect.value)) {
            return selectedCustomerOrder;
        }
        return ORDERS.find(o => String(o.id) === String(orderSelect.value));
    }

    function syncOrder(preserveOld) {
        const order = currentOrder();
        const keep  = preserveOld ? (equipmentSelect.dataset.old || '') : '';
        equipmentSelect.dataset.old = '';
        equipmentSelect.innerHTML = '';
        equipmentLocked.disabled = true;
        equipmentLocked.value = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = order ? '— Select equipment —' : 'Select an order first…';
        equipmentSelect.appendChild(placeholder);

        if (!order) {
            equipmentSelect.disabled = true;
            return;
        }

        order.equipment.forEach(function (unit) {
            const option = document.createElement('option');
            option.value = unit.id;
            option.textContent = unit.label;
            if (String(unit.id) === String(keep)) option.selected = true;
            equipmentSelect.appendChild(option);
        });

        // Single-equipment orders select automatically and lock — there is
        // nothing to choose. The hidden mirror posts the value instead.
        if (order.equipment.length === 1) {
            equipmentSelect.value = String(order.equipment[0].id);
            equipmentSelect.disabled = true;
            equipmentLocked.value = equipmentSelect.value;
            equipmentLocked.disabled = false;
        } else {
            equipmentSelect.disabled = false;
        }
    }

    orderSelect.addEventListener('change', function () { syncOrder(false); });
    syncOrder(true); // restore state after a validation round-trip

    // ── Two order-search windows (same pattern as Orders) ──────────────
    // Either window resolves to the ONE canonical hidden order select —
    // everything downstream (equipment, complaints) is driven by it exactly
    // as before. Category/product/date filters are gone: all of that comes
    // from the order once found.
    const pickers      = document.getElementById('st-order-pickers');
    const selectedChip = document.getElementById('st-order-selected');
    const requiredNote = document.getElementById('st-order-required');

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function showSelectedOrder(order) {
        document.getElementById('st-order-selected-label').textContent = order.label;
        document.getElementById('st-order-selected-date').textContent =
            order.rental_date ? 'Rental date: ' + order.rental_date : 'No delivery date on order';
        selectedChip.classList.remove('hidden');
        selectedChip.classList.add('flex');
        pickers.classList.add('hidden');
        requiredNote.classList.add('hidden');
    }

    function selectOrder(order) {
        selectedCustomerOrder = order;
        // The hidden <select> only needs to carry this one id to submit — the
        // full order object (with equipment) lives in selectedCustomerOrder.
        orderSelect.innerHTML = '<option value="' + order.id + '"></option>';
        orderSelect.value = String(order.id);
        orderSelect.dispatchEvent(new Event('change'));
        showSelectedOrder(order);
    }

    document.getElementById('st-order-change').addEventListener('click', function () {
        selectedCustomerOrder = null;
        orderSelect.value = '';
        orderSelect.dispatchEvent(new Event('change'));
        selectedChip.classList.add('hidden');
        selectedChip.classList.remove('flex');
        pickers.classList.remove('hidden');
        ['st-search-order', 'st-search-customer'].forEach(id => document.getElementById(id).value = '');
    });

    // Live, server-side order search (all orders — not a capped preload — so an
    // order whose unit is soft-assigned through the Queue Line is findable).
    // `by` selects the field: the Order # box matches order/reference number,
    // the Customer box matches customer/billing name.
    function bindOrderSearch(inputId, resultsId, by) {
        const input   = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        let timer = null;
        let lastResults = [];

        function render(list) {
            lastResults = list;
            results.innerHTML = list.length
                ? list.map(o =>
                    `<div data-id="${o.id}" class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                        <span class="font-medium">${esc(o.label)}</span>
                        ${o.rental_date ? `<span class="text-gray-400"> · ${esc(o.rental_date)}</span>` : ''}
                     </div>`).join('')
                : '<div class="px-3 py-2 text-sm text-gray-400">No matching orders</div>';
            results.classList.remove('hidden');
            results.querySelectorAll('[data-id]').forEach(function (row) {
                row.addEventListener('click', function () {
                    const order = lastResults.find(o => String(o.id) === row.dataset.id);
                    results.classList.add('hidden');
                    input.value = '';
                    if (order) selectOrder(order);
                });
            });
        }

        input.addEventListener('input', function () {
            const q = input.value.trim().replace(/^#/, '');
            clearTimeout(timer);
            if (q.length < 2) { results.classList.add('hidden'); return; }
            timer = setTimeout(function () {
                const url = new URL(ORDER_SEARCH_URL, window.location.origin);
                url.searchParams.set('search', q);
                url.searchParams.set('by', by);
                fetch(url.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (payload) { render(payload.data || []); })
                    .catch(function () {
                        results.innerHTML = '<div class="px-3 py-2 text-sm text-red-500">Search failed — try again.</div>';
                        results.classList.remove('hidden');
                    });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!results.contains(e.target) && e.target !== input) results.classList.add('hidden');
        });
    }
    bindOrderSearch('st-search-order', 'st-search-order-results', 'order');
    bindOrderSearch('st-search-customer', 'st-search-customer-results', 'customer');

    // Validation round-trip: restore the previously-chosen order (and its unit)
    // from the server-hydrated selection — it need not be in any preload.
    const oldCustomerEquipmentId = @json(old('ticket_source', 'customer') !== 'standard' ? old('equipment_id') : null);
    (function () {
        const restoreOrder = selectedCustomerOrder
            || ORDERS.find(o => String(o.id) === String(orderSelect.value));
        if (!restoreOrder) return;
        selectedCustomerOrder = restoreOrder;
        orderSelect.innerHTML = '<option value="' + restoreOrder.id + '"></option>';
        orderSelect.value = String(restoreOrder.id);
        equipmentSelect.dataset.old = oldCustomerEquipmentId || '';
        syncOrder(true);           // rebuild equipment, restoring the old unit
        showSelectedOrder(restoreOrder);
        syncComplaintList();
    })();

    // The hidden controls can't carry a native `required` (not focusable) —
    // guard the submit client-side per source; the server re-validates both.
    const stdRequiredNote = document.getElementById('st-std-required');
    document.querySelector('form[action*="tickets"]').addEventListener('submit', function (e) {
        if (currentSource === 'standard') {
            if (!categorySelect.value || !standardEquip.value) {
                e.preventDefault();
                stdRequiredNote.classList.remove('hidden');
                (categorySelect.value ? stdSearch : categorySelect).focus();
            }
            return;
        }
        if (!orderSelect.value) {
            e.preventDefault();
            requiredNote.classList.remove('hidden');
            pickers.classList.remove('hidden');
            selectedChip.classList.add('hidden');
            document.getElementById('st-search-order').focus();
        }
    });

    // Precise matching for the override search (each typed word must appear
    // as an exact substring — "gary" never matches Grayson).
    const preciseSearch = { threshold: 0, ignoreLocation: true, useExtendedSearch: true };

    // ── Equipment ID Override: search aid + reason reveal ─────────────
    const overrideSelect     = document.getElementById('st-override');
    const overrideReasonWrap = document.getElementById('st-override-reason-wrap');

    const overrideChoices = new Choices(overrideSelect, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Search by unit # or equipment name…',
        fuseOptions: preciseSearch,
    });
    overrideSelect.choicesInstance = overrideChoices;

    overrideSelect.addEventListener('change', function () {
        overrideReasonWrap.classList.toggle('hidden', !overrideSelect.value);
    });

    // Product → category map: still needed by the complaint symptom-profile
    // resolution below (the category/product ORDER FILTERS themselves are
    // gone — that context now comes from the selected order).
    const FILTER_PRODUCTS = @json($filterProducts);
    const PRODUCT_CATEGORIES = {};
    FILTER_PRODUCTS.forEach(function (p) { PRODUCT_CATEGORIES[p.id] = p.category_ids || []; });

    // ── Structured complaint intake (categorized symptom library) ──────
    const SYMPTOM_CATEGORIES = @json($symptomCategories);
    const SYMPTOMS           = @json($symptoms);
    const SYMPTOM_PROFILES   = @json($symptomProfiles);
    const OLD_COMPLAINTS     = @json(collect(old('complaints', []))->map(fn ($v) => (int) $v)->values());

    const complaintEmpty     = document.getElementById('st-complaint-empty');
    const complaintList      = document.getElementById('st-complaint-list');
    const complaintChipsWrap = document.getElementById('st-complaint-chips-wrap');
    const complaintChips     = document.getElementById('st-complaint-chips');

    // Selections survive rebuilds; complaints hidden by an equipment change
    // are dropped so an inapplicable selection can never be submitted
    let checkedComplaints = new Set(OLD_COMPLAINTS.map(String));

    function selectedUnit() {
        if (currentSource === 'standard') {
            return standardUnit;
        }
        const order = currentOrder();
        return order ? order.equipment.find(u => String(u.id) === String(equipmentSelect.value)) : null;
    }

    // Resolve the equipment's symptom profile: an exact product-level
    // assignment wins over a broader product-category assignment. Equipment
    // with no matching profile yet shows the full library (same "hide
    // nothing until scoped" default the checklist has always used).
    // Resolution precedence (Equipment problem-templates):
    //   1. the template attached to THIS unit (service_symptom_profile_id)
    //   2. legacy fallback — a profile targeting the unit's product, then
    //      one targeting its product category
    //   3. none → No Template Listed (full library shown as fallback)
    function resolveSymptomProfile(unit) {
        if (unit.symptom_profile_id) {
            const attached = SYMPTOM_PROFILES.find(p => String(p.id) === String(unit.symptom_profile_id));
            if (attached) return attached;
        }

        let profile = SYMPTOM_PROFILES.find(p => p.product_id && String(p.product_id) === String(unit.product_id));
        if (profile) return profile;

        const categories = PRODUCT_CATEGORIES[unit.product_id] || [];
        return SYMPTOM_PROFILES.find(p => p.product_category_id
            && categories.some(id => String(id) === String(p.product_category_id))) || null;
    }

    // The applicable symptoms, each tagged with an `_order` for rendering:
    //   - explicit template (builder-assembled item list, no categories) →
    //     exactly its items, in template order
    //   - legacy profile → included categories + additions − exclusions
    //   - no profile → the full library (fallback), in display order
    function applicableSymptoms(unit) {
        const profile = resolveSymptomProfile(unit);
        if (!profile) return SYMPTOMS.map(s => ({ ...s, _order: s.display_order }));

        const hasCategories = profile.category_ids && profile.category_ids.length;
        if (!hasCategories && profile.items && profile.items.length) {
            const order = new Map(profile.items.map((id, i) => [String(id), i]));
            return SYMPTOMS
                .filter(s => order.has(String(s.id)))
                .map(s => ({ ...s, _order: order.get(String(s.id)) }));
        }

        const categoryIds = (profile.category_ids || []).map(String);
        const additions   = (profile.additions || []).map(String);
        const exclusions  = (profile.exclusions || []).map(String);

        return SYMPTOMS.filter(function (symptom) {
            const id = String(symptom.id);
            if (exclusions.includes(id)) return false;
            if (additions.includes(id)) return true;
            return categoryIds.includes(String(symptom.category_id));
        }).map(s => ({ ...s, _order: s.display_order }));
    }

    function syncComplaintChips() {
        complaintChips.replaceChildren();
        checkedComplaints.forEach(function (id) {
            const symptom = SYMPTOMS.find(s => String(s.id) === id);
            if (!symptom) return;
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 pl-3 pr-1.5 py-1 text-sm font-medium text-amber-800';
            chip.appendChild(document.createTextNode(symptom.name));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'w-4 h-4 rounded-full flex items-center justify-center text-amber-500 hover:text-amber-700 hover:bg-amber-100 text-xs font-bold leading-none';
            remove.textContent = '×';
            remove.title = 'Remove ' + symptom.name;
            remove.addEventListener('click', function () {
                checkedComplaints.delete(id);
                const box = complaintList.querySelector('input[value="' + id + '"]');
                if (box) box.checked = false;
                syncComplaintChips();
            });
            chip.appendChild(remove);
            complaintChips.appendChild(chip);
        });
        complaintChipsWrap.classList.toggle('hidden', checkedComplaints.size === 0);
    }

    const complaintNoTemplate = document.getElementById('st-complaint-no-template');

    function syncComplaintList() {
        const unit = selectedUnit();
        complaintEmpty.classList.toggle('hidden', !!unit);
        complaintList.classList.toggle('hidden', !unit);
        complaintList.replaceChildren();

        if (!unit) {
            checkedComplaints.clear();
            syncComplaintChips();
            complaintNoTemplate.classList.add('hidden');
            return;
        }

        // No Template Listed (spec decision 5): shown when the unit has no
        // attached template and no legacy profile match.
        complaintNoTemplate.classList.toggle('hidden', !!resolveSymptomProfile(unit));

        const applicable = applicableSymptoms(unit);

        // Drop selections the new equipment can no longer report
        checkedComplaints.forEach(function (id) {
            if (!applicable.some(s => String(s.id) === id)) checkedComplaints.delete(id);
        });

        const groups = new Map();
        applicable.forEach(function (symptom) {
            if (!groups.has(symptom.category_id)) {
                const category = SYMPTOM_CATEGORIES.find(c => c.id === symptom.category_id);
                groups.set(symptom.category_id, {
                    label: category ? category.name : 'Other',
                    order: category ? category.display_order : 999,
                    symptoms: [],
                });
            }
            groups.get(symptom.category_id).symptoms.push(symptom);
        });

        Array.from(groups.values()).sort((a, b) => a.order - b.order).forEach(function (group) {
            const box = document.createElement('div');
            box.className = 'rounded-lg border border-gray-200 bg-gray-50 p-3';
            const heading = document.createElement('p');
            heading.className = 'text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2';
            heading.textContent = group.label;
            box.appendChild(heading);

            group.symptoms
                .slice()
                .sort((a, b) => a._order - b._order)
                .forEach(function (symptom) {
                    const row = document.createElement('label');
                    row.className = 'flex items-center gap-2 py-0.5 cursor-pointer';
                    const check = document.createElement('input');
                    check.type = 'checkbox';
                    check.name = 'complaints[]';
                    check.value = symptom.id;
                    check.className = 'st-complaint-check text-amber-600 rounded focus:ring-amber-500';
                    check.checked = checkedComplaints.has(String(symptom.id));
                    check.addEventListener('change', function () {
                        check.checked ? checkedComplaints.add(String(symptom.id)) : checkedComplaints.delete(String(symptom.id));
                        syncComplaintChips();
                    });
                    row.appendChild(check);
                    const text = document.createElement('span');
                    text.className = 'text-sm text-gray-700';
                    text.textContent = symptom.name;
                    row.appendChild(text);
                    box.appendChild(row);
                });
            complaintList.appendChild(box);
        });

        syncComplaintChips();
    }

    equipmentSelect.addEventListener('change', syncComplaintList);
    orderSelect.addEventListener('change', syncComplaintList); // covers single-unit auto-select
    syncComplaintList(); // initial render (validation round-trip restores old checks)

    // ── Complaint evidence: previews with remove-before-save ──────────
    const photoInput  = document.getElementById('st-evidence-photos');
    const videoInput  = document.getElementById('st-evidence-videos');
    const dropZone    = document.getElementById('st-evidence-drop');
    const previews    = document.getElementById('st-evidence-previews');

    document.getElementById('st-evidence-photo-btn').addEventListener('click', () => photoInput.click());
    document.getElementById('st-evidence-video-btn').addEventListener('click', () => videoInput.click());

    function evidenceFiles() {
        return [
            ...Array.from(photoInput.files).map((f, i) => ({ file: f, input: photoInput, index: i })),
            ...Array.from(videoInput.files).map((f, i) => ({ file: f, input: videoInput, index: i })),
        ];
    }

    function removeEvidence(input, index) {
        const keep = new DataTransfer();
        Array.from(input.files).forEach(function (file, i) { if (i !== index) keep.items.add(file); });
        input.files = keep.files;
        syncEvidence();
    }

    function addEvidence(input, files) {
        const merged = new DataTransfer();
        Array.from(input.files).forEach(f => merged.items.add(f));
        Array.from(files).forEach(f => merged.items.add(f));
        input.files = merged.files;
        syncEvidence();
    }

    function syncEvidence() {
        previews.replaceChildren();
        const files = evidenceFiles();
        previews.classList.toggle('hidden', files.length === 0);

        files.forEach(function (entry) {
            const card = document.createElement('div');
            card.className = 'rounded-lg border border-gray-200 bg-white p-2';

            if (entry.file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(entry.file);
                img.className = 'w-full h-20 object-cover rounded-md bg-gray-50';
                img.addEventListener('load', () => URL.revokeObjectURL(img.src));
                card.appendChild(img);
            } else {
                const block = document.createElement('div');
                block.className = 'w-full h-20 rounded-md bg-gray-100 flex items-center justify-center text-[10px] font-bold uppercase tracking-wide text-gray-400';
                block.textContent = 'Video';
                card.appendChild(block);
            }

            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-1 mt-1.5';
            const name = document.createElement('p');
            name.className = 'text-[11px] text-gray-500 truncate';
            name.textContent = entry.file.name;
            name.title = entry.file.name;
            row.appendChild(name);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-xs font-bold text-red-400 hover:text-red-600 shrink-0';
            remove.textContent = '×';
            remove.title = 'Remove ' + entry.file.name;
            remove.addEventListener('click', () => removeEvidence(entry.input, entry.index));
            row.appendChild(remove);
            card.appendChild(row);

            previews.appendChild(card);
        });
    }

    photoInput.addEventListener('change', syncEvidence);
    videoInput.addEventListener('change', syncEvidence);

    ['dragover', 'dragenter'].forEach(function (event) {
        dropZone.addEventListener(event, function (e) {
            e.preventDefault();
            dropZone.classList.add('border-amber-400', 'bg-amber-50');
        });
    });
    ['dragleave', 'drop'].forEach(function (event) {
        dropZone.addEventListener(event, function (e) {
            e.preventDefault();
            dropZone.classList.remove('border-amber-400', 'bg-amber-50');
        });
    });
    dropZone.addEventListener('drop', function (e) {
        const dropped = Array.from(e.dataTransfer.files);
        addEvidence(photoInput, dropped.filter(f => !f.type.startsWith('video/')));
        addEvidence(videoInput, dropped.filter(f => f.type.startsWith('video/')));
    });

    // ── Assign Now panel + team leader rules ───────────────────────
    const panel   = document.getElementById('st-assign-panel');
    const summary = document.getElementById('st-crew-summary');

    document.getElementById('st-assign-toggle').addEventListener('click', function () {
        panel.classList.toggle('hidden');
    });

    // Avatar chip in the same language as partials/_personnel_avatars:
    // initials in a w-7 rounded-full circle; the lead swaps blue for amber
    const crewDisplay  = document.getElementById('st-crew-display');
    const crewLeadWrap = document.getElementById('st-crew-lead-wrap');
    const crewLeadBox  = document.getElementById('st-crew-lead');
    const crewTeamWrap = document.getElementById('st-crew-team-wrap');
    const crewTeamBox  = document.getElementById('st-crew-team');

    function crewChip(name, isLead) {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-2 rounded-full border py-1 pl-1 pr-3 '
            + (isLead ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50');

        const avatar = document.createElement('span');
        avatar.className = 'w-7 h-7 rounded-full text-[10px] font-bold flex items-center justify-center ring-2 ring-white '
            + (isLead ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700');
        avatar.textContent = name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
        avatar.title = name;
        chip.appendChild(avatar);

        const label = document.createElement('span');
        label.className = 'text-sm font-medium ' + (isLead ? 'text-amber-800' : 'text-gray-700');
        label.textContent = name;
        chip.appendChild(label);

        if (isLead) {
            const badge = document.createElement('span');
            badge.className = 'text-[10px] font-bold text-amber-600 uppercase tracking-wide';
            badge.textContent = 'Lead';
            chip.appendChild(badge);
        }

        return chip;
    }

    function syncCrew() {
        const checked = Array.from(document.querySelectorAll('.st-crew-check:checked'));
        let leaderName = null;

        document.querySelectorAll('.st-crew-check').forEach(function (box) {
            const radio = box.closest('div').querySelector('.st-leader-radio');
            radio.disabled = !box.checked;
            if (!box.checked && radio.checked) radio.checked = false;
            if (radio.checked) leaderName = box.dataset.name;
        });

        summary.textContent = checked.length === 0
            ? 'No one assigned yet.'
            : checked.length + ' assigned' + (leaderName ? ' · Team Leader: ' + leaderName : ' · no team leader marked');

        // Rebuild the at-a-glance chips: lead first, then the rest of the team
        crewLeadBox.replaceChildren();
        crewTeamBox.replaceChildren();
        checked.forEach(function (box) {
            const isLead = box.closest('div').querySelector('.st-leader-radio').checked;
            (isLead ? crewLeadBox : crewTeamBox).appendChild(crewChip(box.dataset.name, isLead));
        });
        crewLeadWrap.classList.toggle('hidden', !leaderName);
        crewTeamWrap.classList.toggle('hidden', !crewTeamBox.childElementCount);
        crewDisplay.classList.toggle('hidden', checked.length === 0);
    }

    document.querySelectorAll('.st-crew-check, .st-leader-radio').forEach(function (el) {
        el.addEventListener('change', syncCrew);
    });
    syncCrew();

    // Keep the panel open if a crew was already selected (validation round-trip)
    if (document.querySelector('.st-crew-check:checked')) panel.classList.remove('hidden');

    // ══ Standard Equipment intake: category → server-searched unit ══════
    const categorySelect   = document.getElementById('st-category');
    const stdSearch        = document.getElementById('st-std-search');
    const stdResults       = document.getElementById('st-std-results');
    const stdSelected      = document.getElementById('st-std-selected');
    const stdSelectedLabel = document.getElementById('st-std-selected-label');
    const standardEquip    = document.getElementById('st-std-equipment');
    const storeLabel       = document.getElementById('st-store-label');

    // Enable/disable every control inside a container in one shot — a disabled
    // control never submits, which is how the two same-named equipment_id
    // inputs stay mutually exclusive by path.
    function setControlsDisabled(root, disabled) {
        root.querySelectorAll('input, select, textarea').forEach(function (ctrl) { ctrl.disabled = disabled; });
    }

    function clearStandardEquipment() {
        standardUnit = null;
        standardEquip.value = '';
        stdSearch.value = '';
        stdResults.classList.add('hidden');
        stdSelected.classList.add('hidden');
        stdSelected.classList.remove('flex');
        if (stdRequiredNote) stdRequiredNote.classList.add('hidden');
    }

    // Standard → Customer: equipment must be re-resolved through the order.
    function clearStandard() {
        clearStandardEquipment();
        categorySelect.value = '';
        stdSearch.disabled = true;
        stdSearch.placeholder = 'Select a category first…';
    }

    // Customer → Standard: drop all order/customer/override state.
    function clearCustomer() {
        selectedCustomerOrder = null;
        orderSelect.value = '';
        selectedChip.classList.add('hidden');
        selectedChip.classList.remove('flex');
        pickers.classList.remove('hidden');
        requiredNote.classList.add('hidden');
        ['st-search-order', 'st-search-customer'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const rental = document.querySelector('input[name="rental_date"]');
        if (rental) rental.value = '';
        if (overrideSelect.choicesInstance) {
            overrideSelect.choicesInstance.setChoiceByValue('');
        } else {
            overrideSelect.value = '';
        }
        overrideReasonWrap.classList.add('hidden');
        const reason = document.querySelector('input[name="equipment_override_reason"]');
        if (reason) reason.value = '';
        equipmentSelect.dataset.old = '';
    }

    function applySource(source, opts) {
        opts = opts || {};
        currentSource = source;

        document.querySelectorAll('[data-src]').forEach(function (block) {
            const match = block.dataset.src === source;
            block.classList.toggle('hidden', !match);
            setControlsDisabled(block, !match);
        });

        document.querySelectorAll('.st-source-card').forEach(function (card) {
            const active = card.dataset.source === source;
            // Reuse the order-chip palette (already in the compiled bundle) so
            // this needs no asset rebuild.
            card.classList.toggle('border-blue-200', active);
            card.classList.toggle('bg-blue-50/60', active);
            card.classList.toggle('border-gray-200', !active);
        });

        // Service Store is required on the standard path (no order to infer it).
        if (storeLabel) storeLabel.classList.toggle('required', source === 'standard');

        if (source === 'customer') {
            if (opts.clearOther) clearStandard();
            // The [data-src] loop just re-enabled these controls; syncOrder
            // re-applies the correct disabled state. Seed dataset.old with the
            // current selection so the rebuild never drops it (the early init
            // already consumed the server-side old value).
            equipmentSelect.dataset.old = equipmentSelect.value || equipmentLocked.value || '';
            syncOrder(true);
        } else {
            if (opts.clearOther) clearCustomer();
            // The standard equipment input must be enabled so it can POST.
            standardEquip.disabled = false;
            categorySelect.disabled = false;
            stdSearch.disabled = !categorySelect.value;
            stdSearch.placeholder = categorySelect.value ? 'Search by equipment name or ID…' : 'Select a category first…';
        }

        syncComplaintList();
    }

    function renderStandardResults(list) {
        if (!list.length) {
            stdResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-400">No matching equipment in this category</div>';
            stdResults.classList.remove('hidden');
            return;
        }
        stdResults.innerHTML = list.map(function (u) {
            return '<div data-id="' + u.id + '" class="px-3 py-2 hover:bg-gray-50 cursor-pointer">'
                + '<div class="text-sm font-medium text-gray-800">' + esc(u.name) + '</div>'
                + (u.display_id ? '<div class="text-xs text-gray-400">' + esc(u.display_id) + '</div>' : '')
                + '</div>';
        }).join('');
        stdResults.classList.remove('hidden');
        stdResults.querySelectorAll('[data-id]').forEach(function (row) {
            row.addEventListener('click', function () {
                const unit = list.find(function (u) { return String(u.id) === row.dataset.id; });
                if (unit) selectStandardUnit(unit);
            });
        });
    }

    function selectStandardUnit(unit) {
        standardUnit = unit;
        standardEquip.value = unit.id;
        stdSelectedLabel.textContent = unit.label;
        stdSelected.classList.remove('hidden');
        stdSelected.classList.add('flex');
        stdResults.classList.add('hidden');
        stdSearch.value = '';
        if (stdRequiredNote) stdRequiredNote.classList.add('hidden');
        syncComplaintList();
    }

    let stdSearchTimer = null;
    function runStandardSearch(q) {
        const url = new URL(EQUIPMENT_SEARCH_URL, window.location.origin);
        url.searchParams.set('category_id', categorySelect.value);
        url.searchParams.set('search', q);
        fetch(url.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (payload) { renderStandardResults(payload.data || []); })
            .catch(function () {
                stdResults.innerHTML = '<div class="px-3 py-2 text-sm text-red-500">Search failed — try again.</div>';
                stdResults.classList.remove('hidden');
            });
    }

    categorySelect.addEventListener('change', function () {
        // Changing category immediately clears any previously selected unit.
        clearStandardEquipment();
        const hasCat = !!categorySelect.value;
        stdSearch.disabled = !hasCat;
        stdSearch.placeholder = hasCat ? 'Search by equipment name or ID…' : 'Select a category first…';
        syncComplaintList();
    });

    stdSearch.addEventListener('input', function () {
        const q = stdSearch.value.trim();
        clearTimeout(stdSearchTimer);
        if (!categorySelect.value || q.length < 1) { stdResults.classList.add('hidden'); return; }
        stdSearchTimer = setTimeout(function () { runStandardSearch(q); }, 200);
    });

    document.getElementById('st-std-change').addEventListener('click', function () {
        clearStandardEquipment();
        stdSearch.focus();
    });

    document.addEventListener('click', function (e) {
        if (!stdResults.contains(e.target) && e.target !== stdSearch) stdResults.classList.add('hidden');
    });

    document.querySelectorAll('.st-source-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (this.checked) applySource(this.value, { clearOther: true });
        });
    });

    // Initialize the visible path + fix control enabled/disabled state. On a
    // standard-path validation round-trip, restore the chosen unit's chip.
    applySource(currentSource, { preserve: true });
    if (currentSource === 'standard' && standardUnit) {
        selectStandardUnit(standardUnit);
    }
});
</script>
@endpush
