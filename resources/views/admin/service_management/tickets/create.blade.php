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

    <form method="POST" action="{{ route('admin.service-management.tickets.store') }}">
        @csrf
        <input type="hidden" name="intake" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- ═══════════ MAIN COLUMN: intake cards ═══════════ --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- ===== Card 1: Rental Order Source ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-4 mb-1">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <x-heroicon-o-shopping-cart class="w-4 h-4" />
                            </span>
                            Rental Order Source
                        </h2>
                        <span class="inline-flex items-center gap-2 text-xs text-gray-400">
                            Related to Rental Order:
                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold">Yes</span>
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-400" title="Non-order intake (internal, warranty, customer-owned) is a later phase.">No</span>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">
                        Search by order # or customer name. Only a reference is stored — the Order remains the source of truth for
                        agreements, checklists, photos, and payments.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }} required">Order</label>
                            <select name="order_id" id="st-order" required class="{{ $inputClass }}">
                                <option value="">Search by order # or customer…</option>
                                @foreach ($orderOptions as $order)
                                    <option value="{{ $order['id'] }}" @selected((int) old('order_id') === $order['id'])>{{ $order['label'] }}</option>
                                @endforeach
                            </select>
                            @error('order_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Rental Date</label>
                            <div id="st-rental-date" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-500">
                                Select an order first
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== Card 2: Equipment & Service Location ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                        </span>
                        Equipment &amp; Service Location
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">
                        Where the repair will be managed and how urgent it is. Equipment choices come from the selected order only —
                        single-equipment orders select automatically.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="{{ $labelClass }} required">Equipment</label>
                            <select name="equipment_id" id="st-equipment" required disabled class="{{ $inputClass }}"
                                data-old="{{ old('equipment_id') }}">
                                <option value="">Select an order first…</option>
                            </select>
                            @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Service Store</label>
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
                </div>

                {{-- ===== Card 4: Reported Problem ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                        <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </span>
                        Reported Problem
                    </h2>
                    <div>
                        <label class="{{ $labelClass }}">Complaint</label>
                        <textarea name="customer_complaint" rows="5" class="{{ $inputClass }}"
                            placeholder="The reported problem — from the customer, driver, yard tech, service tech, or counter employee…">{{ old('customer_complaint') }}</textarea>
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

            {{-- ═══════════ RIGHT SIDEBAR: intake summary ═══════════ --}}
            <div class="space-y-6 lg:sticky lg:top-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5 mb-3">
                        <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-gray-400" />
                        Intake Summary
                    </h2>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-xs text-gray-400 pt-0.5">Rental Order</dt>
                            <dd id="st-sum-order" class="text-xs font-medium text-gray-400 text-right">Not selected</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-xs text-gray-400 pt-0.5">Equipment</dt>
                            <dd id="st-sum-equipment" class="text-xs font-medium text-gray-400 text-right">Not selected</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-xs text-gray-400 pt-0.5">Service Store</dt>
                            <dd id="st-sum-store" class="text-xs font-medium text-gray-400 text-right">Not selected</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-xs text-gray-400 pt-0.5">Priority</dt>
                            <dd id="st-sum-priority" class="text-xs font-medium text-gray-700 text-right">Normal</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-xs text-gray-400 pt-0.5">Personnel</dt>
                            <dd id="st-sum-crew" class="text-xs font-medium text-gray-400 text-right">No one assigned</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5 mb-3">
                        <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-gray-400" />
                        What happens next
                    </h2>
                    <ol class="space-y-3">
                        @foreach ([
                            'Ticket opens in Workbench',
                            'Diagnosis is recorded',
                            'Responsibility and approval are determined',
                            'Repair is completed and closed',
                        ] as $step)
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">{{ $loop->iteration }}</span>
                                <span class="text-xs text-gray-600 leading-5">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="text-[11px] text-gray-400 mt-4 pt-3 border-t border-gray-100">
                        Intake captures just enough to open the ticket — everything else happens on the workbench.
                    </p>
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

    const orderSelect     = document.getElementById('st-order');
    const equipmentSelect = document.getElementById('st-equipment');
    const rentalDateBox   = document.getElementById('st-rental-date');

    function syncOrder(preserveOld) {
        const order = ORDERS.find(o => String(o.id) === String(orderSelect.value));
        const keep  = preserveOld ? (equipmentSelect.dataset.old || '') : '';
        equipmentSelect.dataset.old = '';
        equipmentSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = order ? '— Select equipment —' : 'Select an order first…';
        equipmentSelect.appendChild(placeholder);

        if (!order) {
            equipmentSelect.disabled = true;
            rentalDateBox.textContent = 'Select an order first';
            return;
        }

        order.equipment.forEach(function (unit) {
            const option = document.createElement('option');
            option.value = unit.id;
            option.textContent = unit.label;
            if (String(unit.id) === String(keep)) option.selected = true;
            equipmentSelect.appendChild(option);
        });

        // Single-equipment orders select automatically
        if (order.equipment.length === 1) {
            equipmentSelect.value = String(order.equipment[0].id);
        }

        equipmentSelect.disabled = false;
        rentalDateBox.textContent = order.rental_date || 'No delivery date on order';
    }

    orderSelect.addEventListener('change', function () { syncOrder(false); });
    syncOrder(true); // restore state after a validation round-trip

    new Choices(orderSelect, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Type an order # or customer name…',
    });

    // ── Assign Now panel + team leader rules ───────────────────────
    const panel   = document.getElementById('st-assign-panel');
    const summary = document.getElementById('st-crew-summary');

    document.getElementById('st-assign-toggle').addEventListener('click', function () {
        panel.classList.toggle('hidden');
    });

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
    }

    document.querySelectorAll('.st-crew-check, .st-leader-radio').forEach(function (el) {
        el.addEventListener('change', syncCrew);
    });
    syncCrew();

    // Keep the panel open if a crew was already selected (validation round-trip)
    if (document.querySelector('.st-crew-check:checked')) panel.classList.remove('hidden');

    // ── Intake Summary sidebar (display only — reads existing inputs) ──
    const storeSelect    = document.querySelector('select[name="service_store_id"]');
    const prioritySelect = document.querySelector('select[name="priority"]');
    const sum = {
        order:     document.getElementById('st-sum-order'),
        equipment: document.getElementById('st-sum-equipment'),
        store:     document.getElementById('st-sum-store'),
        priority:  document.getElementById('st-sum-priority'),
        crew:      document.getElementById('st-sum-crew'),
    };

    function summaryValue(el, selectedText, emptyText) {
        el.textContent = selectedText || emptyText;
        el.classList.toggle('text-gray-400', !selectedText);
        el.classList.toggle('text-gray-700', !!selectedText);
    }

    function syncSummary() {
        const orderOption = orderSelect.options[orderSelect.selectedIndex];
        summaryValue(sum.order, orderSelect.value ? orderOption.textContent.trim() : '', 'Not selected');

        const equipmentOption = equipmentSelect.options[equipmentSelect.selectedIndex];
        summaryValue(sum.equipment, equipmentSelect.value ? equipmentOption.textContent.trim() : '', 'Not selected');

        const storeOption = storeSelect.options[storeSelect.selectedIndex];
        summaryValue(sum.store, storeSelect.value ? storeOption.textContent.trim() : '', 'Not selected');

        summaryValue(sum.priority, prioritySelect.options[prioritySelect.selectedIndex].textContent.trim(), '');

        const crewCount = document.querySelectorAll('.st-crew-check:checked').length;
        summaryValue(sum.crew, crewCount ? summary.textContent : '', 'No one assigned');
    }

    [orderSelect, equipmentSelect, storeSelect, prioritySelect].forEach(function (el) {
        el.addEventListener('change', syncSummary);
    });
    document.querySelectorAll('.st-crew-check, .st-leader-radio').forEach(function (el) {
        el.addEventListener('change', syncSummary);
    });
    syncSummary();
});
</script>
@endpush
