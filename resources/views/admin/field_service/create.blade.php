@extends('admin.layouts.app')

@section('title', 'New Field Service Request')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\FieldService\FieldMachineStatus;
        use App\Enums\FieldService\FieldOperationalExpectation;
        use App\Enums\FieldService\FieldRecoveryRisk;
        use App\Enums\FieldService\FieldSafetyConcern;
        use App\Enums\FieldService\FieldSiteAccess;
        use App\Enums\FieldService\FieldYesNoUnknown;
        use App\Enums\Service\ServicePriority;
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
                New Field Service Request
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                A field mission: send a technician to assess and stabilize a customer-site incident.
                One question drives this form — <span class="font-medium text-gray-600">what does the technician need before leaving the shop?</span>
            </p>
        </div>
        <a href="{{ route('admin.service-management.board') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Board
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

    <form method="POST" action="{{ route('admin.field-service.tickets.store') }}">
        @csrf

        {{-- ===== 1. Dispatch Information ===== --}}
        @php $oldContactSource = old('contact_source', 'order'); $oldLocationSource = old('location_source', 'delivery'); @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">1 · Dispatch Information</h2>
            <p class="text-xs text-gray-400 mb-4">
                The order answers what the system already knows. You decide only where we go and who we meet.
            </p>

            {{-- Customer Order — the canonical selector. --}}
            <div class="mb-4">
                <label class="{{ $labelClass }} required">Customer Order</label>
                <select name="order_id" id="fs-order" class="{{ $inputClass }}">
                    <option value="">Search order # or customer…</option>
                    @foreach ($orderOptions as $order)
                        <option value="{{ $order['id'] }}" @selected((int) old('order_id') === $order['id'])>{{ $order['label'] }}</option>
                    @endforeach
                </select>
                @error('order_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Customer — display only. --}}
                <div>
                    <label class="{{ $labelClass }}">Customer</label>
                    <div id="fs-customer" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-600">Select an order…</div>
                </div>

                {{-- Equipment — single order: read-only; multiple: selector. --}}
                <div>
                    <label class="{{ $labelClass }} required">Equipment</label>
                    {{-- The one posted equipment id, driven by the JS below. --}}
                    <input type="hidden" name="equipment_id" id="fs-equipment" value="{{ old('equipment_id') }}">
                    <div id="fs-equipment-single" class="hidden px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-800"></div>
                    <div id="fs-equipment-multi" class="hidden space-y-1.5"></div>
                    <div id="fs-equipment-empty" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-400">Select an order…</div>
                    <p id="fs-equipment-detail" class="text-xs text-gray-400 mt-1"></p>
                    @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <input type="hidden" name="serial_number" id="fs-serial" value="{{ old('serial_number') }}">
                </div>
            </div>

            {{-- Contact Person — order contact, or someone else (deliberate). --}}
            <div class="mt-4">
                <label class="{{ $labelClass }}">Who should the technician ask for?</label>
                <div class="flex flex-wrap gap-4 mb-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="contact_source" value="order" class="fs-contact-src" @checked($oldContactSource === 'order')> Order Contact
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="contact_source" value="other" class="fs-contact-src" @checked($oldContactSource === 'other')> Someone Else
                    </label>
                </div>
                @error('contact_source')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                {{-- Order contact (read-only display) --}}
                <div id="fs-contact-order" class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm">
                    <div id="fs-contact-order-name" class="font-medium text-gray-800">—</div>
                    <div id="fs-contact-order-phone" class="text-gray-500">—</div>
                </div>
                {{-- Someone else (intentional entry) --}}
                <div id="fs-contact-other" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }} required">Contact Name</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="{{ $inputClass }}">
                        @error('contact_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">Phone Number</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="{{ $inputClass }}">
                        @error('contact_phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Service Location — delivery address, or a different one (deliberate). --}}
            <div class="mt-4">
                <label class="{{ $labelClass }}">Service Location</label>
                <div class="flex flex-wrap gap-4 mb-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="location_source" value="delivery" class="fs-loc-src" @checked($oldLocationSource === 'delivery')> Delivery Address
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="location_source" value="other" class="fs-loc-src" @checked($oldLocationSource === 'other')> Different Address
                    </label>
                </div>
                @error('location_source')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                <div id="fs-loc-delivery" class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800">—</div>
                <div id="fs-loc-other" class="hidden grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-4">
                        <label class="{{ $labelClass }} required">Street</label>
                        <input type="text" name="loc_street" value="{{ old('loc_street') }}" class="{{ $inputClass }}">
                        @error('loc_street')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }} required">City</label>
                        <input type="text" name="loc_city" value="{{ old('loc_city') }}" class="{{ $inputClass }}">
                        @error('loc_city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">State</label>
                        <input type="text" name="loc_state" value="{{ old('loc_state') }}" class="{{ $inputClass }}">
                        @error('loc_state')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">ZIP</label>
                        <input type="text" name="loc_zip" value="{{ old('loc_zip') }}" class="{{ $inputClass }}">
                        @error('loc_zip')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Reported Problems — the shared shop symptom library. --}}
            <div class="mt-4">
                <label class="{{ $labelClass }}">Reported Problem(s)</label>
                <div class="relative">
                    <input type="text" id="fs-problem-search" autocomplete="off" class="{{ $inputClass }}" placeholder="Search problem library…">
                    <div id="fs-problem-results" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <div id="fs-problem-selected" class="flex flex-wrap gap-2 mt-2"></div>
                <p class="text-xs text-gray-400 mt-1">Not in the list? Type it and choose &ldquo;Add … as a problem,&rdquo; or press Enter.</p>
                @error('complaints')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('complaints.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Additional Details — optional context; never a diagnosis. --}}
            <div class="mt-4">
                <label class="{{ $labelClass }}">Additional Details</label>
                <textarea name="additional_details" rows="2" class="{{ $inputClass }}"
                    placeholder="Anything else the customer reported…">{{ old('additional_details') }}</textarea>
                @error('additional_details')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ===== 2. Media Review ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">2 · Media Review</h2>
            <p class="text-xs text-gray-400 mb-4">
                Pictures and video are operationally critical — confirm what we have before the truck leaves.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach ([
                    'photos_received'           => 'Photos received',
                    'video_received'            => 'Video received',
                    'media_reviewed'            => 'Media reviewed',
                    'additional_media_required' => 'More media required',
                    'media_bypassed'            => 'Media intentionally bypassed',
                ] as $flag => $label)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 cursor-pointer hover:bg-gray-100 transition">
                        <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag))
                            class="text-blue-600 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ===== 3. Dispatch Assessment ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">3 · Dispatch Assessment</h2>
            <p class="text-xs text-gray-400 mb-4">What the office knows about risk and site conditions right now. "Unknown" is a valid answer.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }} required">Priority</label>
                    <select name="priority" required class="{{ $inputClass }}">
                        @foreach (ServicePriority::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('priority', 'normal') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Safety Concern</label>
                    <select name="safety_concern" required class="{{ $inputClass }}">
                        @foreach (FieldSafetyConcern::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('safety_concern', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Machine Status</label>
                    <select name="machine_status" required class="{{ $inputClass }}">
                        @foreach (FieldMachineStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('machine_status', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Machine Stuck</label>
                    <select name="machine_stuck" required class="{{ $inputClass }}">
                        @foreach (FieldYesNoUnknown::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('machine_stuck', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Recovery Risk</label>
                    <select name="recovery_risk" required class="{{ $inputClass }}">
                        @foreach (FieldRecoveryRisk::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('recovery_risk', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Site Access</label>
                    <select name="site_access" required class="{{ $inputClass }}">
                        @foreach (FieldSiteAccess::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('site_access', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $labelClass }}">Site / Access Notes</label>
                    <textarea name="site_notes" rows="2" class="{{ $inputClass }}"
                        placeholder="Gate codes, soft ground, overhead lines, low clearance, parking…">{{ old('site_notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ===== 4. Dispatch Assignment ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">4 · Dispatch Assignment</h2>
            <p class="text-xs text-gray-400 mb-4">
                Optional at creation — assignment can also happen on the Field Operations Workbench when the mission is ready for dispatch.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="{{ $labelClass }}">Assigned Technician</label>
                    <select name="technician_id" class="{{ $inputClass }}">
                        <option value="">— Not assigned yet —</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}" @selected((int) old('technician_id') === $technician->id)>
                                {{ $technician->first_name }} {{ $technician->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Service Truck</label>
                    <select name="truck_id" class="{{ $inputClass }}">
                        <option value="">— Not assigned yet —</option>
                        @foreach ($trucks as $truck)
                            <option value="{{ $truck->id }}" @selected((int) old('truck_id') === $truck->id)>
                                {{ $truck->truck_name }}{{ $truck->truck_number ? ' #' . $truck->truck_number : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Estimated Departure</label>
                    <input type="datetime-local" name="estimated_departure_at"
                        value="{{ old('estimated_departure_at') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Estimated Arrival</label>
                    <input type="datetime-local" name="estimated_arrival_at"
                        value="{{ old('estimated_arrival_at') }}" class="{{ $inputClass }}">
                    @error('estimated_arrival_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Suggested Tools</label>
                    <textarea name="suggested_tools" rows="2" class="{{ $inputClass }}"
                        placeholder="Multimeter, hydraulic gauge set, jump pack…">{{ old('suggested_tools') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Suggested Parts</label>
                    <textarea name="suggested_parts" rows="2" class="{{ $inputClass }}"
                        placeholder="Battery, fuses, common wear parts…">{{ old('suggested_parts') }}</textarea>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="{{ $labelClass }}">Special Instructions</label>
                    <textarea name="special_instructions" rows="2" class="{{ $inputClass }}"
                        placeholder="Anything the technician must know before rolling…">{{ old('special_instructions') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ===== 5. Initial Operational Expectation ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">5 · Initial Operational Expectation</h2>
            <p class="text-xs text-gray-400 mb-4">
                An estimate only — the real outcome is decided by the technician's field assessment.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }} required">Expected Outcome</label>
                    <select name="operational_expectation" required class="{{ $inputClass }}">
                        @foreach (FieldOperationalExpectation::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('operational_expectation', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center gap-3">
            <span class="text-xs text-gray-400 mr-auto">The mission continues on the Field Operations Workbench after creation.</span>
            <a href="{{ route('admin.service-management.board') }}"
                class="px-6 py-3 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Create Field Ticket
            </button>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ORDERS        = @json($orderOptions);
    const PROBLEMS      = @json($problems);
    const PROBLEM_CATS  = @json($problemCategories);
    const OLD_COMPLAINTS = @json(old('complaints', []));
    const OLD_CUSTOM     = @json(old('custom_problems', []));

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
    function $(id) { return document.getElementById(id); }

    const orderSelect = $('fs-order');
    const customerBox = $('fs-customer');
    const equipHidden = $('fs-equipment');   // the posted equipment_id
    const serialHidden = $('fs-serial');
    const eqSingle = $('fs-equipment-single');
    const eqMulti  = $('fs-equipment-multi');
    const eqEmpty  = $('fs-equipment-empty');
    const eqDetail = $('fs-equipment-detail');

    function currentOrder() { return ORDERS.find(o => String(o.id) === String(orderSelect.value)); }

    function setEquipment(u) {
        equipHidden.value  = u.id;
        serialHidden.value = u.serial || '';
        eqDetail.textContent = [u.model, u.serial ? 'SN ' + u.serial : ''].filter(Boolean).join(' · ');
    }

    // Single equipment → read-only; multiple → a selector; none → prompt.
    function renderEquipment(order, keepId) {
        [eqSingle, eqMulti, eqEmpty].forEach(el => el.classList.add('hidden'));
        eqMulti.innerHTML = ''; eqDetail.textContent = '';
        const units = order ? (order.equipment || []) : [];

        if (!order) { eqEmpty.textContent = 'Select an order…'; eqEmpty.classList.remove('hidden'); equipHidden.value = ''; serialHidden.value = ''; return; }
        if (units.length === 0) { eqEmpty.textContent = 'No equipment on this order'; eqEmpty.classList.remove('hidden'); equipHidden.value = ''; serialHidden.value = ''; return; }

        if (units.length === 1) {
            eqSingle.textContent = units[0].label;
            eqSingle.classList.remove('hidden');
            setEquipment(units[0]);
            return;
        }

        eqMulti.classList.remove('hidden');
        let matched = false;
        units.forEach(function (u) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 cursor-pointer hover:bg-gray-100 transition text-sm';
            label.innerHTML = '<input type="radio" name="fs_equipment_choice" value="' + u.id + '"> <span>' + esc(u.label) + '</span>';
            const radio = label.querySelector('input');
            if (String(u.id) === String(keepId)) { radio.checked = true; setEquipment(u); matched = true; }
            radio.addEventListener('change', function () { setEquipment(u); });
            eqMulti.appendChild(label);
        });
        if (!matched) { equipHidden.value = ''; serialHidden.value = ''; eqDetail.textContent = 'Select the machine on site.'; }
    }

    // ── Contact + location radios ──────────────────────────────────────
    const contactOrder = $('fs-contact-order'), contactOther = $('fs-contact-other');
    function syncContactDisplay(order) {
        const c = (order && order.contact) || {};
        $('fs-contact-order-name').textContent  = c.name  || '—';
        $('fs-contact-order-phone').textContent = c.phone || '—';
    }
    function applyContactSource() {
        const src = (document.querySelector('.fs-contact-src:checked') || {}).value || 'order';
        contactOrder.classList.toggle('hidden', src !== 'order');
        contactOther.classList.toggle('hidden', src !== 'other');
    }

    const locDelivery = $('fs-loc-delivery'), locOther = $('fs-loc-other');
    function syncLocationDisplay(order) {
        locDelivery.textContent = (order && order.address && order.address.full) || 'No delivery address on this order';
    }
    function applyLocationSource() {
        const src = (document.querySelector('.fs-loc-src:checked') || {}).value || 'delivery';
        locDelivery.classList.toggle('hidden', src !== 'delivery');
        locOther.classList.toggle('hidden', src !== 'other');
    }

    document.querySelectorAll('.fs-contact-src').forEach(r => r.addEventListener('change', applyContactSource));
    document.querySelectorAll('.fs-loc-src').forEach(r => r.addEventListener('change', applyLocationSource));

    // ── Order orchestration ────────────────────────────────────────────
    function onOrder(preserveEquip) {
        const order = currentOrder();
        customerBox.textContent = order ? (order.customer || 'Unknown customer') : 'Select an order…';
        renderEquipment(order, preserveEquip ? equipHidden.value : '');
        syncContactDisplay(order);
        syncLocationDisplay(order);
    }
    orderSelect.addEventListener('change', function () { onOrder(false); });
    onOrder(true); // restore after a validation round-trip
    applyContactSource();
    applyLocationSource();

    // ── Reported-problem library (search → chips → complaints[]) ────────
    const catName = {}; PROBLEM_CATS.forEach(c => { catName[c.id] = c.name; });
    const search = $('fs-problem-search'), results = $('fs-problem-results'), chips = $('fs-problem-selected');
    const chosen  = new Map();   // library problems: id -> name
    const customs = new Set();   // free-text "Other" problems

    function makeChip(label, name, value, tone, onRemove) {
        const cls = tone === 'amber' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200';
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium ' + cls;
        chip.innerHTML = '<input type="hidden" name="' + name + '" value="' + esc(value) + '"><span>' + esc(label) + '</span><button type="button" class="opacity-60 hover:opacity-100 leading-none">&times;</button>';
        chip.querySelector('button').addEventListener('click', onRemove);
        return chip;
    }
    function renderChips() {
        chips.innerHTML = '';
        chosen.forEach(function (name, id) {
            chips.appendChild(makeChip(name, 'complaints[]', id, 'blue', function () { chosen.delete(id); renderChips(); }));
        });
        customs.forEach(function (text) {
            chips.appendChild(makeChip(text + ' · Other', 'custom_problems[]', text, 'amber', function () { customs.delete(text); renderChips(); }));
        });
    }
    function resetSearch() { search.value = ''; results.classList.add('hidden'); search.focus(); }
    function addProblem(id, name) { if (!chosen.has(id)) { chosen.set(id, name); renderChips(); } resetSearch(); }
    function addCustom(text) { text = (text || '').trim(); if (text && !customs.has(text)) { customs.add(text); renderChips(); } resetSearch(); }

    function renderResults(raw) {
        const q = raw.trim();
        if (!q) { results.classList.add('hidden'); return; }
        const ql = q.toLowerCase();
        const matches = PROBLEMS.filter(p => !chosen.has(p.id) && p.name.toLowerCase().includes(ql)).slice(0, 20);
        let html = matches.map(p => '<div data-id="' + p.id + '" class="fs-lib px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"><span class="text-gray-800">' + esc(p.name) + '</span> <span class="text-gray-400 text-xs">' + esc(catName[p.category_id] || '') + '</span></div>').join('');
        // The "Other" path — always offer to add the typed text as a problem.
        html += '<div class="fs-other px-3 py-2 hover:bg-amber-50 cursor-pointer text-sm border-t border-gray-100 text-amber-700 font-medium">&plus; Add &ldquo;' + esc(q) + '&rdquo; as a problem</div>';
        results.innerHTML = html;
        results.classList.remove('hidden');
        results.querySelectorAll('.fs-lib').forEach(function (row) {
            row.addEventListener('click', function () { const p = matches.find(m => String(m.id) === row.dataset.id); if (p) addProblem(p.id, p.name); });
        });
        results.querySelector('.fs-other').addEventListener('click', function () { addCustom(q); });
    }
    search.addEventListener('input', function () { renderResults(search.value); });
    // Enter adds the typed text as an "Other" problem (no forced library match).
    search.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addCustom(search.value); } });
    document.addEventListener('click', function (e) { if (!results.contains(e.target) && e.target !== search) results.classList.add('hidden'); });

    // Restore selections after a validation round-trip.
    (OLD_COMPLAINTS || []).forEach(function (id) { const p = PROBLEMS.find(x => String(x.id) === String(id)); if (p) chosen.set(p.id, p.name); });
    (OLD_CUSTOM || []).forEach(function (t) { if (t && String(t).trim() !== '') customs.add(String(t).trim()); });
    renderChips();

    new Choices(orderSelect, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Type an order # or customer name…',
    });
});
</script>
@endpush
