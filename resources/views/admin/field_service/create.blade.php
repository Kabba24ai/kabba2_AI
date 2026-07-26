@extends('admin.layouts.app')

@section('title', 'New Field Service Ticket')

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
                New Field Service Ticket
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

        {{-- ===== 1. Source / Incident Information ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">1 · Incident Information</h2>
            <p class="text-xs text-gray-400 mb-4">
                Assumes remote diagnosis already happened. When the AI Technician module ships, this section will pre-fill from the handoff.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }}">Related Rental Order</label>
                    <select name="order_id" id="fs-order" class="{{ $inputClass }}">
                        <option value="">No related order</option>
                        @foreach ($orderOptions as $order)
                            <option value="{{ $order['id'] }}" @selected((int) old('order_id') === $order['id'])>{{ $order['label'] }}</option>
                        @endforeach
                    </select>
                    @error('order_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Customer</label>
                    <div id="fs-customer" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-500">
                        Derived from the selected order
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Equipment</label>
                    <select name="equipment_id" id="fs-equipment" class="{{ $inputClass }}" data-old="{{ old('equipment_id') }}">
                        <option value="">— Select equipment —</option>
                    </select>
                    @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Serial Number</label>
                    <input type="text" name="serial_number" id="fs-serial" value="{{ old('serial_number') }}"
                        class="{{ $inputClass }}" placeholder="If available…">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Contact Person</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="{{ $inputClass }}"
                        placeholder="Who the technician asks for on site">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="{{ $inputClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }} required">Job Site Address</label>
                    <input type="text" name="job_site_address" value="{{ old('job_site_address') }}" required
                        class="{{ $inputClass }}" placeholder="Where the technician is going">
                    @error('job_site_address')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Date / Time Reported</label>
                    <input type="datetime-local" name="reported_at" required
                        value="{{ old('reported_at', now()->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                    @error('reported_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $labelClass }} required">Problem Summary</label>
                    <textarea name="problem_summary" rows="3" required class="{{ $inputClass }}"
                        placeholder="What the customer reported…">{{ old('problem_summary') }}</textarea>
                    @error('problem_summary')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Diagnostic Summary / Handoff Notes</label>
                    <textarea name="diagnostic_summary" rows="3" class="{{ $inputClass }}"
                        placeholder="What remote troubleshooting already covered, and why a technician is needed…">{{ old('diagnostic_summary') }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">AI Technician Session</label>
                    <input type="text" name="ai_session_reference" value="{{ old('ai_session_reference') }}"
                        class="{{ $inputClass }}" placeholder="Session reference (optional)">
                    <p class="text-xs text-gray-400 mt-1">Future AI Technician handoff reference.</p>
                </div>
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
    const ORDERS    = @json($orderOptions);
    const EQUIPMENT = @json($equipmentOptions);

    const orderSelect     = document.getElementById('fs-order');
    const equipmentSelect = document.getElementById('fs-equipment');
    const customerBox     = document.getElementById('fs-customer');
    const serialInput     = document.getElementById('fs-serial');
    let serialAutofilled  = false;

    function equipmentChoicesFor(order) {
        // With an order: only that order's equipment. Without: full list.
        return order && order.equipment.length ? order.equipment : EQUIPMENT;
    }

    function syncOrder(preserveOld) {
        const order = ORDERS.find(o => String(o.id) === String(orderSelect.value));
        const keep  = preserveOld ? (equipmentSelect.dataset.old || '') : '';
        equipmentSelect.dataset.old = '';
        equipmentSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Select equipment —';
        equipmentSelect.appendChild(placeholder);

        equipmentChoicesFor(order).forEach(function (unit) {
            const option = document.createElement('option');
            option.value = unit.id;
            option.textContent = unit.label;
            option.dataset.serial = unit.serial || '';
            if (String(unit.id) === String(keep)) option.selected = true;
            equipmentSelect.appendChild(option);
        });

        if (order && order.equipment.length === 1 && !keep) {
            equipmentSelect.value = String(order.equipment[0].id);
        }

        customerBox.textContent = order
            ? (order.customer || 'Unknown customer')
            : 'Derived from the selected order';
        syncSerial();
    }

    function syncSerial() {
        const selected = equipmentSelect.options[equipmentSelect.selectedIndex];
        const serial   = selected ? (selected.dataset.serial || '') : '';
        if (serial && (!serialInput.value || serialAutofilled)) {
            serialInput.value = serial;
            serialAutofilled = true;
        }
    }

    orderSelect.addEventListener('change', function () { syncOrder(false); });
    equipmentSelect.addEventListener('change', syncSerial);
    serialInput.addEventListener('input', function () { serialAutofilled = false; });
    syncOrder(true); // restore state after a validation round-trip

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
