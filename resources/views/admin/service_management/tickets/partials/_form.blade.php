@php
    use App\Enums\Service\FinancialResponsibility;
    use App\Enums\Service\FinancialStatus;
    use App\Enums\Service\RepairStatus;
    use App\Enums\Service\ServiceLocation;
    use App\Enums\Service\ServicePriority;
    use App\Enums\Service\ServiceType;

    $selectedPersonnel = collect(old('personnel', $ticket->personnel?->pluck('id')->all() ?? []))->map(fn ($v) => (int) $v)->all();
    $currentStatus     = old('repair_status', $ticket->repair_status?->value ?? RepairStatus::Open->value);
    $hasOrder          = old('related_to_order', $ticket->order_id ? 'yes' : 'no') === 'yes';
    $inputClass        = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none';
    $labelClass        = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

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

{{-- ===== Ticket basics ===== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-4">Ticket Details</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="{{ $labelClass }} required">Service Type</label>
            <select name="service_type" class="{{ $inputClass }}" required>
                <option value="">Select…</option>
                @foreach (ServiceType::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('service_type', $ticket->service_type?->value) === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Service Location</label>
            <select name="service_location" class="{{ $inputClass }}" required>
                @foreach (ServiceLocation::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('service_location', $ticket->service_location?->value ?? 'in_shop') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Priority</label>
            <select name="priority" class="{{ $inputClass }}" required>
                @foreach (ServicePriority::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('priority', $ticket->priority?->value ?? 'normal') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Opened Date</label>
            <input type="date" name="opened_at" class="{{ $inputClass }}" required
                value="{{ old('opened_at', ($ticket->opened_at ?? now())->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="{{ $labelClass }} required">Equipment</label>
            <select name="equipment_id" class="choices-select {{ $inputClass }}" required>
                <option value="">Select Equipment…</option>
                @foreach ($equipmentList as $eq)
                    <option value="{{ $eq->id }}" @selected((int) old('equipment_id', $ticket->equipment_id) === $eq->id)>
                        {{ $eq->equipment_name }} ({{ $eq->equipment_id }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Repair Status</label>
            <select name="repair_status" id="repair-status-select" class="{{ $inputClass }}" required>
                @foreach (RepairStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($currentStatus === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Financial Responsibility</label>
            <select name="financial_responsibility" class="{{ $inputClass }}" required>
                @foreach (FinancialResponsibility::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('financial_responsibility', $ticket->financial_responsibility?->value ?? 'pending') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }} required">Financial Status</label>
            <select name="financial_status" class="{{ $inputClass }}" required>
                @foreach (FinancialStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('financial_status', $ticket->financial_status?->value ?? 'not_billable') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Blocked context — required when a waiting status is selected --}}
    <div id="blocked-fields" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 {{ in_array($currentStatus, RepairStatus::blocked(), true) ? '' : 'hidden' }}">
        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-3">Blocking Details (required for waiting statuses)</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="{{ $labelClass }}">Blocked Reason</label>
                <input type="text" name="blocked_reason" class="{{ $inputClass }}"
                    placeholder="e.g. Final drive on backorder from OEM"
                    value="{{ old('blocked_reason', $ticket->blocked_reason) }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Expected Action Date</label>
                <input type="date" name="expected_action_date" class="{{ $inputClass }}"
                    value="{{ old('expected_action_date', $ticket->expected_action_date?->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
</div>

{{-- ===== Order association ===== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-1">Related to a Rental Order?</h2>
    <p class="text-xs text-gray-400 mb-4">Only a reference is stored (order, customer, equipment, rental date) — the Order remains the source of truth.</p>
    <div class="flex items-center gap-6 mb-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="related_to_order" value="no" class="text-blue-600 focus:ring-blue-500" @checked(!$hasOrder)>
            <span class="text-sm text-gray-700">No</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="related_to_order" value="yes" class="text-blue-600 focus:ring-blue-500" @checked($hasOrder)>
            <span class="text-sm text-gray-700">Yes</span>
        </label>
    </div>
    <div id="order-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 {{ $hasOrder ? '' : 'hidden' }}">
        <div>
            <label class="{{ $labelClass }}">Order</label>
            <select name="order_id" class="choices-select {{ $inputClass }}">
                <option value="">Search by order # or customer…</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}" @selected((int) old('order_id', $ticket->order_id) === $order->id)>
                        {{ $order->order_number }} — {{ $order->customer_name ?? $order->customer?->full_name ?? 'Unknown' }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Customer and rental date fill from the selected order.</p>
        </div>
        <div>
            <label class="{{ $labelClass }}">Rental Date (override)</label>
            <input type="date" name="rental_date" class="{{ $inputClass }}"
                value="{{ old('rental_date', $ticket->rental_date?->format('Y-m-d')) }}">
        </div>
    </div>
</div>

{{-- ===== Personnel ===== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-1">Assigned Personnel</h2>
    <p class="text-xs text-gray-400 mb-4">Pulled from HRM employee records. Select multiple to assign a crew.</p>
    <select name="personnel[]" class="choices-select {{ $inputClass }}" multiple>
        @foreach ($employees as $employee)
            <option value="{{ $employee->id }}" @selected(in_array($employee->id, $selectedPersonnel, true))>
                {{ $employee->first_name }} {{ $employee->last_name }}
            </option>
        @endforeach
    </select>
</div>

{{-- ===== Repair documentation ===== --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-4">Repair Documentation</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ([
            'customer_complaint'   => 'Customer Complaint',
            'technician_diagnosis' => 'Technician Diagnosis',
            'root_cause'           => 'Root Cause',
            'repair_summary'       => 'Repair Summary',
        ] as $field => $label)
            <div>
                <label class="{{ $labelClass }}">{{ $label }}</label>
                <textarea name="{{ $field }}" rows="3" class="{{ $inputClass }}"
                    placeholder="{{ $label }}…">{{ old($field, $ticket->{$field}) }}</textarea>
            </div>
        @endforeach
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Show blocking details only for waiting statuses
    const blockedStatuses = @json(\App\Enums\Service\RepairStatus::blocked());
    const statusSelect    = document.getElementById('repair-status-select');
    const blockedFields   = document.getElementById('blocked-fields');
    function toggleBlocked() {
        blockedFields.classList.toggle('hidden', !blockedStatuses.includes(statusSelect.value));
    }
    statusSelect?.addEventListener('change', toggleBlocked);

    // Show order picker only when the ticket relates to a rental order
    const orderFields = document.getElementById('order-fields');
    document.querySelectorAll('input[name="related_to_order"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const isYes = this.value === 'yes';
            orderFields.classList.toggle('hidden', !isYes);
            if (!isYes) {
                const orderSelect = orderFields.querySelector('select[name="order_id"]');
                if (orderSelect) orderSelect.value = '';
                const rentalInput = orderFields.querySelector('input[name="rental_date"]');
                if (rentalInput) rentalInput.value = '';
            }
        });
    });
});
</script>
@endpush
