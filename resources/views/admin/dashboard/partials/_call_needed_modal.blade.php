{{-- Call Needed Modal (shared: dashboard + task manager) --}}
<div id="CallNeededModal"
    style="display: none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">

    <div class="w-full mx-auto max-w-lg">
       <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden max-h-[90vh] flex flex-col">

            {{-- Modal Header --}}
            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b">
                <div>
                    <h2 id="callModalTitle" class="text-lg font-semibold text-gray-900">
                        Call Needed
                    </h2>
                    <p class="text-sm text-gray-500">
                        Assign customer call reminder
                    </p>
                </div>

                <button type="button"
                    onclick="closeCallNeededModal()"
                    class="text-gray-400 hover:text-gray-700 text-xl">
                    &times;
                </button>
            </div>
            <div class="overflow-y-auto">
            {{-- Modal Body --}}
            {{ html()->form()
                    ->id('callNeededForm')
                    ->attributes([
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'px-6 pt-6 pb-5 space-y-4',
                    ])
                    ->open()
                }}

                <input type="hidden" id="call_needed_id" value="">

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Call For
                    </label>

                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="radio"
                                name="contact_type"
                                value="customer"
                                checked
                                class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Customer</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="radio"
                                name="contact_type"
                                value="supplier"
                                class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Supplier</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="radio"
                                name="contact_type"
                                value="manual"
                                class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Other</span>
                        </label>
                    </div>
                </div>

                {{-- Customer select --}}
                <div class="w-full" id="customer-section">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">
                        Customer
                    </label>

                    <select name="customer_id" id="call_customer_id"
                        class="choices-select w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        required>

                        <option value="">Select Customer</option>

                        @foreach ($customers as $customer)

                            @php
                                $fullName = trim((string) $customer->full_name);
                                $phone = trim((string) $customer->phone);
                                $email = trim((string) $customer->email);
                            @endphp

                            @if ($fullName || $phone)
                                <option value="{{ $customer->id }}">
                                    {{ $fullName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $phone ? App\Helpers\CustomHelper::formatPhone($phone) : '' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $email }}
                                </option>
                            @endif

                        @endforeach

                    </select>
                </div>

                {{-- Supplier select --}}
                <div id="supplier-section" class="w-full hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">
                        Supplier
                    </label>
                    <select name="supplier_id" id="call_supplier_id"
                        class="choices-select w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? '    ·    ' . \App\Helpers\CustomHelper::formatPhone($supplier->phone) : '' }}{{ $supplier->primary_contact_name ? '    ·    ' . $supplier->primary_contact_name : '' }}</option>
                        @endforeach
                    </select>
                </div>

               <div id="manual-contact-section" class="hidden">
                    <div class="grid grid-cols-2 gap-4 space-y-3">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">
                                Name
                            </label>

                            <input
                                type="text"
                                id="contact_name"
                                name="contact_name"
                                placeholder="Enter Name"
                                class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">
                                Phone
                            </label>

                            <input
                                type="text"
                                id="contact_phone"
                                name="contact_phone"
                                placeholder="(xxx) xxx-xxxx"
                                class="masked-phone w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>

                    </div>

                    <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email
                            </label>

                            <input
                                type="email"
                                id="contact_email"
                                name="contact_email"
                                placeholder="Enter Email"
                                class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                    </div>

                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">
                        Assign To
                    </label>

                    <select
                        name="assigned_to"
                        id="call_assigned_to"
                        class="choices-select w-full"
                        required
                    >
                        <option value="">Select Assignee</option>

                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->full_name ?? $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reason --}}
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">
                        Reason
                    </label>

                   {!! html()->select('reason', [
                        '' => 'Select Reason',
                        'contract_renewal'       => 'Contract Renewal',
                        'delivery_pickup'        => 'Delivery / Pickup',
                        'equipment_availability' => 'Equipment Availability',
                        'equipment_return'       => 'Equipment Return',
                        'general_followup'       => 'General Follow-up',
                        'maintenance_request'    => 'Maintenance Request',
                        'order_review'           => 'Order Review',
                        'payment_followup'       => 'Payment Follow-up',
                        'rental_inquiry'         => 'Rental Inquiry',

                    ], old('reason'))
                    ->id('call_reason')
                    ->class('choices-select w-full')
                    ->required()
                    !!}
                </div>

                <div class="flex items-center rounded-lg border border-gray-200 p-3 bg-gray-50">

                    <input
                        type="checkbox"
                        id="call_is_urgent"
                        class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">

                    <label
                            for="call_is_urgent"
                            class="ml-3 text-sm font-medium text-gray-700">

                            <span class="flex items-center gap-2">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="w-4 h-4 text-red-600">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m0 3.75h.007v.008H12v-.008zM10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z" />
                                </svg>

                                <span>Mark as Urgent</span>

                            </span>

                            <span class="block text-xs text-gray-500 font-normal mt-1">
                                High priority call reminder
                            </span>

                        </label>

                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>

                   {!! html()->textarea('notes')
                        ->id('call_notes')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                        ->rows(3)
                        ->placeholder('Enter call notes...')
                    !!}
                </div>

                {{-- Buttons --}}
               <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        onclick="closeCallNeededModal()"
                        class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>

                   <button
                        type="button"
                        id="call-needed-save-btn"
                        onclick="saveCallNeeded()"
                        class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-teal-700">

                        <span id="callBtnText">
                            Save
                        </span>

                        <svg
                            id="callBtnSpinner"
                            xmlns="http://www.w3.org/2000/svg"
                            class="hidden animate-spin h-5 w-5 text-white"
                            fill="none"
                            viewBox="0 0 24 24">

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4">
                            </circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                            </path>

                        </svg>

                    </button>

                </div>

            {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</div>


<div id="CompleteCallModal"
    style="display:none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 hidden">

    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl border border-gray-200">

        <div class="flex justify-between items-center px-6 py-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Complete Call
                </h2>
                <p class="text-sm text-gray-500">
                    Add call outcome and notes
                </p>
            </div>
            <button type="button" onclick="closeCompleteCallModal()" class="text-gray-400 hover:text-gray-700 text-xl">
                &times;
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="complete_call_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">
                    Call Action
                </label>

                <select id="complete_call_status" class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm" required>
                    <option value="">Select Status</option>
                    <option value="completed">Completed</option>
                    <option value="resolved">Resolved</option>
                    <option value="no_answer">No Answer</option>
                    <option value="no_answer_followed_up_text">No Answer, Followed Up With Text</option>
                    <option value="left_voicemail">Left Voicemail</option>
                    <option value="follow_up_needed">Follow-up Needed</option>
                    <option value="not_interested">Not Interested</option>
                </select>
            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Notes
                </label>

                <textarea
                    id="complete_call_description"
                    rows="4"
                    class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm"
                    placeholder="Write what happened during the call..."></textarea>
            </div>

            <div class="flex items-center justify-between gap-3 pt-3 flex-wrap">

                {{-- Close / Cancel --}}
                <button
                    type="button"
                    onclick="closeCompleteCallModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close
                </button>

                <div class="flex items-center gap-2 flex-wrap">

                    {{-- Update & Remain Open --}}
                    <button
                        type="button"
                        id="call-action-save-btn"
                        onclick="submitCompleteCall('save')"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span id="callActionBtnText">Update &amp; Remain Open</span>
                        <svg id="callActionBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>

                    {{-- Save & Close --}}
                    <button
                        type="button"
                        onclick="submitCompleteCall('complete')"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save &amp; Close
                    </button>

                    {{-- Update and Reschedule --}}
                    <button
                        type="button"
                        onclick="openRescheduleStep()"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-orange-500 text-white text-sm font-medium hover:bg-orange-600 transition whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Update and Reschedule
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reschedule Follow-Up Modal --}}
<div id="RescheduleCallModal"
    style="display:none;"
    class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/60 px-4 hidden">

    <div class="bg-white rounded-lg shadow-xl w-full max-w-md border border-gray-200">

        <div class="flex justify-between items-center px-6 py-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Reschedule Follow-Up</h2>
                <p class="text-sm text-gray-500">Set when this call should return to the active dashboard</p>
            </div>
            <button type="button" onclick="closeRescheduleCallModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
        </div>

        <div class="px-6 py-5 space-y-4">

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-800">
                    <strong>Note saved.</strong> This call will be hidden from the active dashboard and automatically reappear at the selected date and time.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Follow-up Date <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="reschedule_follow_up_date"
                            placeholder="Pick a date"
                            readonly
                            class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:border-orange-400 focus:ring-1 focus:ring-orange-400 cursor-pointer bg-white">
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Follow-up Time <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="time"
                            id="reschedule_follow_up_time"
                            class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:border-orange-400 focus:ring-1 focus:ring-orange-400">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reminder Note <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                    id="reschedule_follow_up_note"
                    rows="3"
                    class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:border-orange-400 focus:ring-1 focus:ring-orange-400"
                    placeholder="Add context for the follow-up call..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">

                <button
                    type="button"
                    onclick="closeRescheduleCallModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancel
                </button>

                <button
                    type="button"
                    id="reschedule-confirm-btn"
                    onclick="submitRescheduleCall()"
                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-orange-500 text-white text-sm font-medium hover:bg-orange-600 transition whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="rescheduleBtnText">Confirm Reschedule</span>
                    <svg id="rescheduleBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>

            </div>
        </div>
    </div>
</div>

@push('js')

<script>

// Module-level state for the two-step reschedule flow
let _rescheduleCallId = null;
let _rescheduleCallStatus = null;
let _rescheduleCallNote = null;
let _rescheduleFlatpickr = null;

function initRescheduleDatePicker()
{
    function doInit() {
        if (_rescheduleFlatpickr) {
            _rescheduleFlatpickr.clear();
            return;
        }
        _rescheduleFlatpickr = flatpickr('#reschedule_follow_up_date', {
            dateFormat:    'Y-m-d',
            altInput:      true,
            altFormat:     'F j, Y',
            minDate:       'today',
            disableMobile: true,
        });
    }

    if (window.flatpickr) {
        doInit();
        return;
    }

    if (!document.getElementById('flatpickr-css')) {
        const link  = document.createElement('link');
        link.id     = 'flatpickr-css';
        link.rel    = 'stylesheet';
        link.href   = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        document.head.appendChild(link);
    }

    const script    = document.createElement('script');
    script.src      = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
    script.onload   = doInit;
    document.head.appendChild(script);
}

function openRescheduleStep()
{
    const status      = document.getElementById('complete_call_status').value;
    const description = document.getElementById('complete_call_description').value.trim();

    if (!status) {
        notyf.error('Please select call status.');
        return;
    }

    if (!description) {
        notyf.error('Please enter call summary.');
        return;
    }

    _rescheduleCallId     = document.getElementById('complete_call_id').value;
    _rescheduleCallStatus = status;
    _rescheduleCallNote   = description;

    document.getElementById('reschedule_follow_up_time').value = '';
    document.getElementById('reschedule_follow_up_note').value = '';

    closeCompleteCallModal();

    const modal = document.getElementById('RescheduleCallModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');

    initRescheduleDatePicker();
}

function closeRescheduleCallModal()
{
    const modal = document.getElementById('RescheduleCallModal');
    modal.style.display = 'none';
    modal.classList.add('hidden');
    _rescheduleCallId = _rescheduleCallStatus = _rescheduleCallNote = null;
}

function submitRescheduleCall()
{
    const followUpDate = document.getElementById('reschedule_follow_up_date').value;
    const followUpTime = document.getElementById('reschedule_follow_up_time').value;
    const followUpNote = document.getElementById('reschedule_follow_up_note').value.trim();

    if (!followUpDate) {
        notyf.error('Please select a follow-up date.');
        return;
    }

    if (!followUpTime) {
        notyf.error('Please select a follow-up time.');
        return;
    }

    const followUpAt = followUpDate + ' ' + followUpTime + ':00';
    const callId     = _rescheduleCallId;

    const btn     = document.getElementById('reschedule-confirm-btn');
    const btnText = document.getElementById('rescheduleBtnText');
    const spinner = document.getElementById('rescheduleBtnSpinner');

    btn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    fetch(
        "{{ route('admin.dashboard.call-needed.reschedule', ':id') }}"
            .replace(':id', callId),
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                call_status:      _rescheduleCallStatus,
                completion_note:  _rescheduleCallNote,
                follow_up_at:     followUpAt,
                follow_up_note:   followUpNote || null,
            }),
        }
    )
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            notyf.success(data.message);
            closeRescheduleCallModal();

            if (window.taskManagerMode) {
                window.location.reload();
            } else {
                const card = document.querySelector(`[data-call-id="${callId}"]`);
                if (card) card.remove();
                loadCallNeededList();
            }
        } else {
            notyf.error(data.message || 'Something went wrong');
        }
    })
    .catch(err => {
        console.error('Reschedule call error:', err);
        notyf.error('Failed to reschedule call');
    })
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Confirm Reschedule';
        spinner.classList.add('hidden');
    });
}

function openCompleteCallModal(id)
{
    document.getElementById('complete_call_id').value = id;
    document.getElementById('complete_call_status').value = '';
    document.getElementById('complete_call_description').value = '';

    const modal = document.getElementById('CompleteCallModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function closeCompleteCallModal()
{
    const modal = document.getElementById('CompleteCallModal');
    modal.style.display = 'none';
    modal.classList.add('hidden');
}

function submitCompleteCall(action)
{
    const id = document.getElementById('complete_call_id').value;
    const status = document.getElementById('complete_call_status').value;
    const description = document.getElementById('complete_call_description').value.trim();

    if (!status) {
        notyf.error('Please select call status.');
        return;
    }

    if (!description) {
        notyf.error('Please enter call summary.');
        return;
    }

    const btn = document.getElementById('call-action-save-btn');
    const btnText = document.getElementById('callActionBtnText');
    const spinner = document.getElementById('callActionBtnSpinner');

    btn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    fetch(
        "{{ route('admin.dashboard.call-needed.complete', ':id') }}"
            .replace(':id', id),
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({
                action: action,
                call_status: status,
                completion_note: description,
            }),
        }
    )
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            notyf.success(data.message);
            closeCompleteCallModal();

            if (window.taskManagerMode) {
                window.location.reload();
            } else {
                if (action === 'complete') {
                    const card = document.querySelector(`[data-call-id="${id}"]`);
                    if (card) card.remove();
                }
                loadCallNeededList();
            }

        } else {

            notyf.error(data.message || 'Something went wrong');
        }
    })
    .catch(err => {

        console.error('Complete call error:', err);
        notyf.error('Failed to save call action');

    })
    .finally(() => {

        btn.disabled = false;
        btnText.textContent = 'Save Action';
        spinner.classList.add('hidden');

    });
}

function openCallModalOnly()
{
    const modal = document.getElementById('CallNeededModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function openCallNeededModal()
{
    document.querySelector('input[name="contact_type"][value="customer"]').checked = true;

    document.getElementById('call_needed_id').value = '';

    document.getElementById('callModalTitle').innerText =
        'Add Call Reminder';

    document.getElementById('callBtnText').innerText =
        'Save';

    document.getElementById('callNeededForm').reset();

    document.getElementById('contact_name').value  = '';
    document.getElementById('contact_email').value = '';
    document.getElementById('contact_phone').value = '';

    if (window.callCustomerChoices)  window.callCustomerChoices.removeActiveItems();
    if (window.callSupplierChoices)  window.callSupplierChoices.removeActiveItems();

    document.getElementById('supplier-section').classList.add('hidden');
    document.getElementById('customer-section').classList.remove('hidden');
    document.getElementById('manual-contact-section').classList.add('hidden');

    document.getElementById('call_is_urgent').checked = false;

    if (window.callAssigneeChoices) window.callAssigneeChoices.removeActiveItems();
    if (window.callReasonChoices)   window.callReasonChoices.removeActiveItems();

    const modal = document.getElementById('CallNeededModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function closeCallNeededModal() {
    const modal = document.getElementById('CallNeededModal');
    modal.style.display = 'none';
    modal.classList.add('hidden');
}

function saveCallNeeded() {

    const contactType = document.querySelector(
        'input[name="contact_type"]:checked'
    ).value;

    const customerId   = document.getElementById('call_customer_id').value;
    const supplierId   = document.getElementById('call_supplier_id')?.value || null;
    const contactName  = document.getElementById('contact_name')?.value.trim();
    const contactEmail = document.getElementById('contact_email')?.value.trim();
    const contactPhone = document.getElementById('contact_phone')?.value.trim();
    const reason       = document.getElementById('call_reason').value;
    const notes        = document.getElementById('call_notes').value;
    const assignedTo   = document.getElementById('call_assigned_to').value;
    const isUrgent     = document.getElementById('call_is_urgent').checked;

    if (contactType === 'customer') {

        if (!customerId) {
            notyf.error('Please select customer.');
            return;
        }

    } else if (contactType === 'supplier') {

        if (!supplierId) {
            notyf.error('Please select a supplier.');
            return;
        }

    } else {

        if (!contactName) {
            notyf.error('Please enter name.');
            return;
        }

        if (!contactPhone) {
            notyf.error('Please enter phone.');
            return;
        }
    }

    if (!reason) {
        notyf.error('Please select reason.');
        return;
    }

    if (!assignedTo) {
        notyf.error('Please select assignee.');
        return;
    }

    const saveBtn = document.getElementById('call-needed-save-btn');
    const btnText = document.getElementById('callBtnText');
    const spinner = document.getElementById('callBtnSpinner');

    saveBtn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    const callId = document.getElementById('call_needed_id').value;

    const url = callId
        ? "{{ route('admin.dashboard.call-needed.update', ':id') }}"
            .replace(':id', callId)
        : "{{ route('admin.dashboard.call-needed.store') }}";

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
        body: JSON.stringify({
            customer_id:   contactType === 'customer' ? customerId : null,
            supplier_id:   contactType === 'supplier' ? supplierId : null,
            contact_name:  contactType === 'manual' ? contactName  : null,
            contact_email: contactType === 'manual' ? contactEmail : null,
            contact_phone: contactType === 'manual' ? contactPhone : null,
            assigned_to:   assignedTo,
            reason:        reason,
            notes:         notes,
            is_urgent:     isUrgent ? 1 : 0,
        }),
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            notyf.success(data.message);
            document.getElementById('call_needed_id').value = '';
            document.getElementById('callNeededForm').reset();
            window.callCustomerChoices.removeActiveItems();
            window.callReasonChoices.removeActiveItems();
            document.getElementById('call_notes').value = '';

            closeCallNeededModal();

            if (window.taskManagerMode) {
                window.location.reload();
            } else {
                loadCallNeededList();
            }

        } else {

            notyf.error(data.message || 'Something went wrong');

        }
    })
    .catch(() => {

        notyf.error('Failed to save call reminder');

    })
    .finally(() => {

        saveBtn.disabled = false;

        btnText.textContent =
            document.getElementById('call_needed_id').value
                ? 'Update'
                : 'Save';

        spinner.classList.add('hidden');

    });
}

function loadCallNeededList()
{
    if (window.taskManagerMode) return;

    fetch("{{ route('admin.dashboard.call-needed.list') }}")
    .then(res => res.json())
    .then(data => {

        if (!data.success) return;

        window.callNeededData = data.data;

        const filtered = window.activeAssigneeFilter
            ? data.data.filter(c => (c.assignee?.id ?? 'unassigned') == window.activeAssigneeFilter)
            : data.data;

        renderCallNeededList(filtered);

        const countEl = document.getElementById('call-needed-count');
        if (countEl) countEl.innerText = data.count + ' New';
    });
}

// Palette cycles through for each unique assignee
const ASSIGNEE_PALETTE = [
    { bg: '#DBEAFE', color: '#1D4ED8', border: '#BFDBFE', activeBg: '#2563EB', activeColor: '#FFFFFF' },
    { bg: '#F3E8FF', color: '#6D28D9', border: '#DDD6FE', activeBg: '#7C3AED', activeColor: '#FFFFFF' },
    { bg: '#FEF3C7', color: '#B45309', border: '#FDE68A', activeBg: '#D97706', activeColor: '#FFFFFF' },
    { bg: '#DCFCE7', color: '#15803D', border: '#BBF7D0', activeBg: '#16A34A', activeColor: '#FFFFFF' },
    { bg: '#FFE4E6', color: '#BE123C', border: '#FECDD3', activeBg: '#E11D48', activeColor: '#FFFFFF' },
    { bg: '#E0F2FE', color: '#0369A1', border: '#BAE6FD', activeBg: '#0284C7', activeColor: '#FFFFFF' },
];

window.activeAssigneeFilter = null;

function renderAssigneeFilters(calls) {
    const container = document.getElementById('assignee-filters');
    if (!container) return;

    const assigneeMap = {};
    let colorIndex = 0;
    calls.forEach(call => {
        const id = call.assignee?.id ?? 'unassigned';
        const name = call.assignee?.full_name ?? 'Unassigned';
        if (!assigneeMap[id]) {
            assigneeMap[id] = { name, count: 0, colorIndex: colorIndex++ };
        }
        assigneeMap[id].count++;
    });

    if (!Object.keys(assigneeMap).length) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = Object.entries(assigneeMap).map(([id, info]) => {
        const c = ASSIGNEE_PALETTE[info.colorIndex % ASSIGNEE_PALETTE.length];
        const isActive = window.activeAssigneeFilter == id;
        const bg    = isActive ? c.activeBg    : c.bg;
        const color = isActive ? c.activeColor : c.color;
        return `
            <button
                onclick="filterCallsByAssignee('${id}')"
                style="background:${bg};color:${color};border:1px solid ${c.border};transition:all .15s"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer select-none"
                data-assignee-id="${id}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ${info.name} (${info.count})
            </button>`;
    }).join('');
}

function filterCallsByAssignee(assigneeId) {
    window.activeAssigneeFilter = (window.activeAssigneeFilter == assigneeId) ? null : assigneeId;

    const calls = window.callNeededData || [];
    const filtered = window.activeAssigneeFilter
        ? calls.filter(c => (c.assignee?.id ?? 'unassigned') == window.activeAssigneeFilter)
        : calls;

    renderAssigneeFilters(calls);
    renderCallNeededList(filtered, true);
}

function renderCallNeededList(calls, skipFilters = false)
{
    const container = document.getElementById('call-needed-list');
    if (!container) return;

    if (!skipFilters) renderAssigneeFilters(calls);

    if (!calls.length) {

        container.innerHTML = `
            <div class="text-center py-10 text-gray-500">
                No active call reminders.
            </div>
        `;

        return;
    }

    container.innerHTML = calls.map(call => `
       <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition" data-call-id="${call.id}">

    <div class="flex justify-between gap-4">

        <div class="flex-1">

            <!-- Header -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-500">
                    ${call.contact_type === 'customer' ? 'Customer:' : call.contact_type === 'supplier' ? 'Supplier:' : 'Contact Person:'}
                </span>
                <h3 class="text-sm font-semibold text-gray-900">
                    ${call.customer.full_name}
                </h3>

                 ${call.customer.phone ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                        ${call.customer.phone}
                    </span>
                ` : ''}

                ${call.customer.email ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                        ${call.customer.email}
                    </span>
                ` : ''}

                ${call.is_urgent ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200">
                        Urgent
                    </span>
                ` : ''}

                ${call.was_rescheduled ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200">
                        Returned from reschedule
                    </span>
                ` : ''}

            </div>

            <div class="mt-3 border-l-4 border-blue-200 bg-blue-50/40 rounded-r-lg p-3">

                <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">

                    <span>
                        <span class="font-semibold text-gray-700">Reason:</span>
                        <span class="text-gray-600">
                            ${formatReason(call.reason)}
                        </span>
                    </span>

                    ${call.notes ? `
                        <span>
                            <span class="font-semibold text-gray-700">Notes:</span>
                            <span class="text-gray-600">
                                ${call.notes}
                            </span>
                        </span>
                    ` : ''}

                </div>

            </div>

            <!-- Footer -->
           <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-gray-500">

                <span class="inline-flex items-center gap-1">
                    <span class="font-medium">Assigned To:</span>
                    ${call.assignee?.full_name ?? '-'}
                </span>

                <span class="inline-flex items-center gap-1">
                    <span class="font-medium">Created By:</span>
                    ${call.creator?.full_name ?? '-'}
                </span>

                <span class="inline-flex items-center gap-1">
                    ${call.created_at}
                </span>

           </div>

           <div
                id="call-activities-${call.id}"
                class="hidden overflow-hidden transition-all duration-300 ease-in-out"
                style="max-height:0">
            </div>

        </div>

        <!-- Actions -->
        <div class="flex flex-col items-center gap-1 border-l border-gray-100 pl-3">

            <div class="flex items-center gap-1">
                <button
                    onclick="viewCallNeeded(${call.id})"
                    class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>

                <button
                    onclick="openCompleteCallModal(${call.id})"
                    class="p-2 rounded-lg text-green-600 hover:bg-green-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>

            <button
                onclick="toggleCallActivities(${call.id})"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap">
                Activity (${call.activities.length})
            </button>

        </div>

    </div>

</div>
    `).join('');
}

function toggleCallActivities(id)
{
    const box = document.getElementById(
        `call-activities-${id}`
    );

    const call = window.callNeededData ? window.callNeededData.find(c => c.id == id) : null;

    if (!call || !box) return;

    box.innerHTML = `
<div class="mt-4 ml-3 border-l-2 border-blue-200 pl-6">

    ${call.activities.map(activity => `

        <div class="relative pb-6">

            <div class="absolute -left-[33px] top-1 w-5 h-5 rounded-full bg-blue-500 border-4 border-white shadow"></div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            ${formatReason(activity.status)}
                        </span>

                    </div>

                 <div class="text-xs text-gray-500">
    Updated by <span class="font-medium text-gray-700">${activity.user?.full_name ?? '-'}</span>
    on ${activity.created_at}
</div>

                </div>

                <div class="mt-3 rounded-lg bg-gray-50 border border-gray-100 p-3">

                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">
                        Notes
                    </div>

                    <p class="text-sm text-gray-700 leading-relaxed">
                        ${activity.notes}
                    </p>

                </div>

            </div>

        </div>

    `).join('')}

</div>
`;

    if (box.classList.contains('hidden')) {
        box.classList.remove('hidden');
        box.style.maxHeight = box.scrollHeight + 'px';
    } else {
        box.style.maxHeight = '0px';
        setTimeout(() => {
            box.classList.add('hidden');
        }, 250);
    }
}

function clearCallNeeded(id)
{
    window.showConfirm(
        'Are you sure you want to clear this call reminder?',
        'Clear Call Reminder'
    ).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        fetch(
            "{{ route('admin.dashboard.call-needed.clear', ':id') }}"
                .replace(':id', id),
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                }
            }
        )
        .then(res => res.json())
        .then(data => {

            if (data.success) {

                notyf.success(data.message);
                document.getElementById('call_needed_id').value = '';
                loadCallNeededList();
            }
        });
    });
}

function formatReason(reason) {
    if (!reason) return '';

    return reason
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase());
}

function viewCallNeeded(id)
{
    fetch(
        "{{ route('admin.dashboard.call-needed.show', ':id') }}"
        .replace(':id', id)
    )
    .then(res => res.json())
    .then(data => {

        if (!data.success) {
            notyf.error('Unable to load call.');
            return;
        }

        const call = data.data;

        document.getElementById('call_needed_id').value =
            call.id;

        const customerRadio  = document.querySelector('input[name="contact_type"][value="customer"]');
        const supplierRadio  = document.querySelector('input[name="contact_type"][value="supplier"]');
        const manualRadio    = document.querySelector('input[name="contact_type"][value="manual"]');

        document.getElementById('customer-section').classList.add('hidden');
        document.getElementById('supplier-section').classList.add('hidden');
        document.getElementById('manual-contact-section').classList.add('hidden');

        if (call.customer_id) {

            customerRadio.checked = true;
            document.getElementById('customer-section').classList.remove('hidden');
            window.callCustomerChoices.setChoiceByValue(String(call.customer_id));
            if (window.callSupplierChoices) window.callSupplierChoices.removeActiveItems();

        } else if (call.supplier_id) {

            supplierRadio.checked = true;
            document.getElementById('supplier-section').classList.remove('hidden');
            if (window.callSupplierChoices) window.callSupplierChoices.setChoiceByValue(String(call.supplier_id));
            window.callCustomerChoices.removeActiveItems();

        } else {

            manualRadio.checked = true;
            document.getElementById('manual-contact-section').classList.remove('hidden');

            document.getElementById('contact_name').value  = call.contact_name  ?? '';
            document.getElementById('contact_email').value = call.contact_email ?? '';
            document.getElementById('contact_phone').value = call.contact_phone ?? '';

            window.callCustomerChoices.removeActiveItems();
            if (window.callSupplierChoices) window.callSupplierChoices.removeActiveItems();
        }

        window.callAssigneeChoices.setChoiceByValue(
            String(call.created_by)
        );

        window.callReasonChoices.setChoiceByValue(
            call.reason
        );

        document.getElementById('call_notes').value =
            call.notes ?? '';

        document.getElementById('call_is_urgent').checked =
            Boolean(call.is_urgent);

        document.getElementById('callModalTitle').innerText =
            'Edit Call Reminder';

        document.getElementById('callBtnText').innerText =
            'Update';

        openCallModalOnly();
    });
}

document.addEventListener('DOMContentLoaded', function () {

    if (!window.callCustomerChoices) {
        window.callCustomerChoices = new Choices(
            document.getElementById('call_customer_id'),
            {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                searchResultLimit: 1000,
                renderChoiceLimit: -1,
            }
        );
    }

    if (!window.callAssigneeChoices) {
        window.callAssigneeChoices = new Choices(
            document.getElementById('call_assigned_to'),
            {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                searchResultLimit: 1000,
                renderChoiceLimit: -1,
            }
        );
    }

    if (!window.callReasonChoices) {
        window.callReasonChoices = new Choices(
            document.getElementById('call_reason'),
            {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                searchResultLimit: 1000,
                renderChoiceLimit: -1,
            }
        );
    }

    if (!window.callSupplierChoices) {
        window.callSupplierChoices = new Choices(
            document.getElementById('call_supplier_id'),
            {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                searchResultLimit: 1000,
                renderChoiceLimit: -1,
            }
        );
    }

    if (!window.taskManagerMode) {
        loadCallNeededList();
    }
});

document.addEventListener('change', function (e) {

    if (e.target.name !== 'contact_type') {
        return;
    }

    const val = e.target.value;

    document.getElementById('customer-section').classList.toggle('hidden', val !== 'customer');
    document.getElementById('supplier-section').classList.toggle('hidden', val !== 'supplier');
    document.getElementById('manual-contact-section').classList.toggle('hidden', val !== 'manual');
});
</script>

@endpush
