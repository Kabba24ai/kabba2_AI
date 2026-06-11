@extends('admin.layouts.app')

@section('title', 'Calls Log Report')

@push('css')
@endpush

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                <x-heroicon-o-phone class="w-5 h-5 text-red-600" />
            </div>
            <div>
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Calls Log Report</h3>
                <p class="text-sm text-gray-500">Full historical record of all call reminders and activity</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-4 sm:space-y-0 mb-6">
        <div class="flex flex-wrap items-end gap-4 w-full">

            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear
                </button>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="search_name" placeholder="Customer name"
                        value="{{ request('search_name') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="search_company" placeholder="Customer company"
                        value="{{ request('search_company') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-38">
                <div class="relative bg-white">
                    <input type="text" id="search_phone" placeholder="(xxx) xxx-xxxx"
                        value="{{ request('search_phone') }}"
                        class="masked-phone pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-phone
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <select id="search_admin"
                    class="border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-full focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Admins</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(request('search_admin') == $user->id)>
                            {{ $user->full_name ?? $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- Card List --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">

        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-gray-700">All Call Records</h4>
            <span class="text-xs text-gray-400">Newest first</span>
        </div>

        <div id="calls-log-wrapper">
            @include('admin.reports.calls_log.partials._table', ['calls' => $calls])
        </div>

    </div>

@endsection

{{-- =================== EDIT MODAL =================== --}}
<div id="CallNeededModal"
    style="display: none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">

    <div class="w-full mx-auto max-w-lg">
        <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden max-h-[90vh] flex flex-col">

            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b">
                <div>
                    <h2 id="callModalTitle" class="text-lg font-semibold text-gray-900">Edit Call Reminder</h2>
                    <p class="text-sm text-gray-500">Update customer call reminder</p>
                </div>
                <button type="button" onclick="closeCallNeededModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="overflow-y-auto">
                {{ html()->form()
                    ->id('callNeededForm')
                    ->attributes([
                        'autocomplete' => 'off',
                        'class' => 'px-6 pt-6 pb-5 space-y-4',
                    ])
                    ->open()
                }}

                <input type="hidden" id="call_needed_id" value="">

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Call For</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="contact_type" value="customer" checked class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Customer</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="contact_type" value="manual" class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Other-Customer</span>
                        </label>
                    </div>
                </div>

                <div class="w-full" id="customer-section">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Customer</label>
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
                                    {{ $fullName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $phone ? \App\Helpers\CustomHelper::formatPhone($phone) : '' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $email }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div id="manual-contact-section" class="hidden">
                    <div class="grid grid-cols-2 gap-4 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Name</label>
                            <input type="text" id="contact_name" name="contact_name" placeholder="Enter Name"
                                class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Phone</label>
                            <input type="text" id="contact_phone" name="contact_phone" placeholder="(xxx) xxx-xxxx"
                                class="masked-phone w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="contact_email" name="contact_email" placeholder="Enter Email"
                            class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                    </div>
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Assign To</label>
                    <select name="assigned_to" id="call_assigned_to" class="choices-select w-full" required>
                        <option value="">Select Assignee</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name ?? $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Reason</label>
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
                    <input type="checkbox" id="call_is_urgent" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <label for="call_is_urgent" class="ml-3 text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-600" />
                            <span>Mark as Urgent</span>
                        </span>
                        <span class="block text-xs text-gray-500 font-normal mt-1">High priority call reminder</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    {!! html()->textarea('notes')
                        ->id('call_notes')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                        ->rows(3)
                        ->placeholder('Enter call notes...')
                    !!}
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" onclick="closeCallNeededModal()"
                        class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="button" id="call-needed-save-btn" onclick="saveCallNeeded()"
                        class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-teal-700">
                        <span id="callBtnText">Update</span>
                        <svg id="callBtnSpinner" xmlns="http://www.w3.org/2000/svg"
                            class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>

                {{ html()->form()->close() }}
            </div>

        </div>
    </div>
</div>

{{-- =================== COMPLETE CALL MODAL =================== --}}
<div id="CompleteCallModal"
    style="display:none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 hidden">

    <div class="bg-white rounded-lg shadow-xl w-full max-w-md border border-gray-200">

        <div class="flex justify-between items-center px-6 py-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Complete Call</h2>
                <p class="text-sm text-gray-500">Add call outcome and notes</p>
            </div>
            <button type="button" onclick="closeCompleteCallModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="complete_call_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Call Action</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="complete_call_description" rows="4"
                    class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm"
                    placeholder="Write what happened during the call..."></textarea>
            </div>

            <div class="flex items-center justify-between pt-3">

                <button type="button" onclick="closeCompleteCallModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" id="call-action-save-btn" onclick="submitCompleteCall('save')"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span id="callActionBtnText">Update &amp; Remain Open</span>
                        <svg id="callActionBtnSpinner" xmlns="http://www.w3.org/2000/svg"
                            class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>

                    <button type="button" onclick="submitCompleteCall('complete')"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save &amp; Close
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

@push('js')
<script>

// ── Filters ──────────────────────────────────────────────────────────────────
(function () {
    const wrapper     = document.getElementById('calls-log-wrapper');
    const nameInput   = document.getElementById('search_name');
    const companyInput= document.getElementById('search_company');
    const phoneInput  = document.getElementById('search_phone');
    const adminSelect = document.getElementById('search_admin');
    const clearBtn    = document.getElementById('clear-filters');
    let debounceTimer = null;

    function fetchCalls(page = 1) {
        const params = new URLSearchParams();
        if (nameInput.value)    params.set('search_name',    nameInput.value);
        if (companyInput.value) params.set('search_company', companyInput.value);
        if (phoneInput.value)   params.set('search_phone',   phoneInput.value);
        if (adminSelect.value)  params.set('search_admin',   adminSelect.value);
        params.set('page', page);

        wrapper.classList.add('opacity-50', 'pointer-events-none');

        fetch("{{ route('admin.reports.calls-log.index') }}?" + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) wrapper.innerHTML = data.html;
        })
        .catch(() => {})
        .finally(() => wrapper.classList.remove('opacity-50', 'pointer-events-none'));
    }

    function debounce(fn, ms = 350) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fn, ms);
    }

    [nameInput, companyInput, phoneInput].forEach(el =>
        el.addEventListener('input', () => debounce(() => fetchCalls(1)))
    );
    adminSelect.addEventListener('change', () => fetchCalls(1));

    clearBtn.addEventListener('click', function () {
        nameInput.value    = '';
        companyInput.value = '';
        phoneInput.value   = '';
        adminSelect.value  = '';
        fetchCalls(1);
    });

    Paginator.init({ wrapper, fetchCallback: fetchCalls });

    fetchCalls(1);
})();

// ── Activity toggle (server-rendered cards, no JS data needed) ───────────────
function toggleCallActivities(id) {
    const box = document.getElementById('call-activities-' + id);
    if (!box) return;

    if (box.classList.contains('hidden')) {
        box.classList.remove('hidden');
        box.style.maxHeight = box.scrollHeight + 'px';
    } else {
        box.style.maxHeight = '0px';
        setTimeout(() => box.classList.add('hidden'), 250);
    }
}

// ── Complete Call Modal ───────────────────────────────────────────────────────
function openCompleteCallModal(id) {
    document.getElementById('complete_call_id').value = id;
    document.getElementById('complete_call_status').value = '';
    document.getElementById('complete_call_description').value = '';

    const modal = document.getElementById('CompleteCallModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function closeCompleteCallModal() {
    const modal = document.getElementById('CompleteCallModal');
    modal.style.display = 'none';
    modal.classList.add('hidden');
}

function submitCompleteCall(action) {
    const id     = document.getElementById('complete_call_id').value;
    const status = document.getElementById('complete_call_status').value;
    const note   = document.getElementById('complete_call_description').value.trim();

    if (!status) {
        notyf.error('Please select call status.');
        return;
    }

    const btn     = document.getElementById('call-action-save-btn');
    const btnText = document.getElementById('callActionBtnText');
    const spinner = document.getElementById('callActionBtnSpinner');

    btn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    fetch(
        "{{ route('admin.dashboard.call-needed.complete', ':id') }}".replace(':id', id),
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ action: action, call_status: status, completion_note: note }),
        }
    )
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            notyf.success(data.message);
            closeCompleteCallModal();
            window.location.reload();
        } else {
            notyf.error(data.message || 'Something went wrong');
        }
    })
    .catch(() => notyf.error('Failed to save call action'))
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Update & Remain Open';
        spinner.classList.add('hidden');
    });
}

// ── Edit Modal ────────────────────────────────────────────────────────────────
function openCallModalOnly() {
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
    const contactType  = document.querySelector('input[name="contact_type"]:checked').value;
    const customerId   = document.getElementById('call_customer_id').value;
    const contactName  = document.getElementById('contact_name')?.value.trim();
    const contactEmail = document.getElementById('contact_email')?.value.trim();
    const contactPhone = document.getElementById('contact_phone')?.value.trim();
    const reason       = document.getElementById('call_reason').value;
    const notes        = document.getElementById('call_notes').value;
    const assignedTo   = document.getElementById('call_assigned_to').value;
    const isUrgent     = document.getElementById('call_is_urgent').checked;

    if (contactType === 'customer' && !customerId) {
        notyf.error('Please select customer.');
        return;
    }
    if (contactType === 'manual' && !contactName) {
        notyf.error('Please enter name.');
        return;
    }
    if (contactType === 'manual' && !contactPhone) {
        notyf.error('Please enter phone.');
        return;
    }
    if (!reason)     { notyf.error('Please select reason.');   return; }
    if (!assignedTo) { notyf.error('Please select assignee.'); return; }

    const saveBtn = document.getElementById('call-needed-save-btn');
    const btnText = document.getElementById('callBtnText');
    const spinner = document.getElementById('callBtnSpinner');

    saveBtn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    const callId = document.getElementById('call_needed_id').value;
    const url    = callId
        ? "{{ route('admin.dashboard.call-needed.update', ':id') }}".replace(':id', callId)
        : "{{ route('admin.dashboard.call-needed.store') }}";

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            customer_id:   contactType === 'customer' ? customerId : null,
            contact_name:  contactName,
            contact_email: contactEmail,
            contact_phone: contactPhone,
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
            closeCallNeededModal();
            window.location.reload();
        } else {
            notyf.error(data.message || 'Something went wrong');
        }
    })
    .catch(() => notyf.error('Failed to save call reminder'))
    .finally(() => {
        saveBtn.disabled = false;
        btnText.textContent = 'Update';
        spinner.classList.add('hidden');
    });
}

function viewCallNeeded(id) {
    fetch(
        "{{ route('admin.dashboard.call-needed.show', ':id') }}".replace(':id', id)
    )
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            notyf.error('Unable to load call.');
            return;
        }

        const call = data.data;

        document.getElementById('call_needed_id').value = call.id;

        const customerRadio = document.querySelector('input[name="contact_type"][value="customer"]');
        const manualRadio   = document.querySelector('input[name="contact_type"][value="manual"]');

        if (call.customer_id) {
            customerRadio.checked = true;
            document.getElementById('customer-section').classList.remove('hidden');
            document.getElementById('manual-contact-section').classList.add('hidden');
            window.callCustomerChoices.setChoiceByValue(String(call.customer_id));
        } else {
            manualRadio.checked = true;
            document.getElementById('customer-section').classList.add('hidden');
            document.getElementById('manual-contact-section').classList.remove('hidden');
            document.getElementById('contact_name').value  = call.contact_name  ?? '';
            document.getElementById('contact_email').value = call.contact_email ?? '';
            document.getElementById('contact_phone').value = call.contact_phone ?? '';
            window.callCustomerChoices.removeActiveItems();
        }

        window.callAssigneeChoices.setChoiceByValue(String(call.created_by));
        window.callReasonChoices.setChoiceByValue(call.reason);
        document.getElementById('call_notes').value       = call.notes     ?? '';
        document.getElementById('call_is_urgent').checked = Boolean(call.is_urgent);
        document.getElementById('callModalTitle').innerText = 'Edit Call Reminder';
        document.getElementById('callBtnText').innerText    = 'Update';

        openCallModalOnly();
    });
}

// ── Choices.js init ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    window.callCustomerChoices = new Choices(
        document.getElementById('call_customer_id'),
        { searchEnabled: true, shouldSort: false, itemSelectText: '', searchResultLimit: 1000, renderChoiceLimit: -1 }
    );

    window.callAssigneeChoices = new Choices(
        document.getElementById('call_assigned_to'),
        { searchEnabled: true, shouldSort: false, itemSelectText: '', searchResultLimit: 1000, renderChoiceLimit: -1 }
    );

    window.callReasonChoices = new Choices(
        document.getElementById('call_reason'),
        { searchEnabled: true, shouldSort: false, itemSelectText: '', searchResultLimit: 1000, renderChoiceLimit: -1 }
    );
});

document.addEventListener('change', function (e) {
    if (e.target.name !== 'contact_type') return;
    const isCustomer = e.target.value === 'customer';
    document.getElementById('customer-section').classList.toggle('hidden', !isCustomer);
    document.getElementById('manual-contact-section').classList.toggle('hidden', isCustomer);
});

</script>
@endpush
