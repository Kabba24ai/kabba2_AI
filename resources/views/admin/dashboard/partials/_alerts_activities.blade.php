<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">

                {{-- Header --}}
        <div class="flex items-center justify-between mb-4">

            <div class="flex items-center gap-2">

                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                    <x-heroicon-o-bell-alert class="w-5 h-5 text-red-600" />
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Alerts & Activities
                    </h2>

                    <p class="text-sm text-gray-500">
                        Recent system activities and reminders
                    </p>
                </div>

            </div>

            {{-- Right Side --}}
            <div class="flex items-center gap-3">

                <button
                    type="button"
                    onclick="openCallNeededModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium shadow-sm hover:bg-red-700 transition-all">

                    <x-heroicon-o-phone class="w-4 h-4" />

                    Call Needed
                </button>

                <span id="call-needed-count"
                    class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    0 New
                </span>

            </div>

        </div>

                {{-- Alerts List --}}
        <div id="call-needed-list"
            class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
        </div>

</div>
{{-- Call Needed Modal --}}
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
                                value="manual"
                                class="text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Non-Customer</span>
                        </label>
                    </div>
                </div>

                {{-- Assign To --}}
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

                            {{-- Skip customers where all 3 fields are empty --}}
                            @if ($fullName || $phone || $email)
                                <option value="{{ $customer->id }}">
                                    {{ $fullName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $phone ? App\Helpers\CustomHelper::formatPhone($phone) : '' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $email }}
                                </option>
                            @endif

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

@push('js')

<script>
   function openCallModalOnly()
{
    const modal = document.getElementById('CallNeededModal');

    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

    function openCallNeededModal()
{
    document.querySelector(
        'input[value="customer"]'
    ).checked = true;

    document
        .getElementById('customer-section')
        .classList.remove('hidden');

    document
        .getElementById('manual-contact-section')
        .classList.add('hidden');

    document.getElementById('call_needed_id').value = '';

    document.getElementById('callModalTitle').innerText =
        'Add Call Reminder';

    document.getElementById('callBtnText').innerText =
        'Save';

    document.getElementById('callNeededForm').reset();

    document.getElementById('contact_name').value = '';
    document.getElementById('contact_email').value = '';
    document.getElementById('contact_phone').value = '';

    if (window.callCustomerChoices) {
    window.callCustomerChoices.removeActiveItems();
}

document.getElementById('call_is_urgent').checked = false;

if (window.callAssigneeChoices) {
    window.callAssigneeChoices.removeActiveItems();
}

if (window.callReasonChoices) {
    window.callReasonChoices.removeActiveItems();
}

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

        const customerId  = document.getElementById('call_customer_id').value;
        const contactName = document.getElementById('contact_name')?.value.trim();
        const contactEmail = document.getElementById('contact_email')?.value.trim();
        const contactPhone = document.getElementById('contact_phone')?.value.trim();
        const reason     = document.getElementById('call_reason').value;
        const notes      = document.getElementById('call_notes').value;
        const assignedTo = document.getElementById('call_assigned_to').value;
        const isUrgent =
    document.getElementById('call_is_urgent').checked;

        if (contactType === 'customer') {

            if (!customerId) {
                notyf.error('Please select customer.');
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
                customer_id: contactType === 'customer' ? customerId : null,
                contact_name: contactName,
                contact_email: contactEmail,
                contact_phone: contactPhone,
                assigned_to: assignedTo,
                reason: reason,
                notes: notes,
                is_urgent: isUrgent ? 1 : 0,
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

              
                loadCallNeededList();
                closeCallNeededModal();

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
    fetch("{{ route('admin.dashboard.call-needed.list') }}")
    .then(res => res.json())
    .then(data => {

        if (!data.success) {
            return;
        }

        renderCallNeededList(data.data);

        document.getElementById('call-needed-count').innerText =
            data.count + ' New';
    });
}


function renderCallNeededList(calls)
{
    const container = document.getElementById('call-needed-list');

    if (!calls.length) {

        container.innerHTML = `
            <div class="text-center py-10 text-gray-500">
                No active call reminders.
            </div>
        `;

        return;
    }

    container.innerHTML = calls.map(call => `
       <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition">

    <div class="flex justify-between gap-4">

        <div class="flex-1">

            <!-- Header -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-500">
                    ${call.customer.id ? 'Customer:' : 'Contact Person:'}
                </span>
                <h3 class="text-sm font-semibold text-gray-900">
                    ${call.customer.full_name}
                </h3>

                 ${call.customer.phone ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                        <x-heroicon-o-phone class="w-3.5 h-3.5" />
                        ${call.customer.phone}
                    </span>
                ` : ''}

                ${call.customer.email ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                        <x-heroicon-o-envelope class="w-3.5 h-3.5" />
                        ${call.customer.email}
                    </span>
                ` : ''}

                ${call.is_urgent ? `
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200">
                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                        Urgent
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
                    <x-heroicon-o-user class="w-3.5 h-3.5" />
                    <span class="font-medium">Assigned To:</span>
                    ${call.assignee?.full_name ?? '-'}
                </span>

                <span class="inline-flex items-center gap-1">
                    <x-heroicon-o-user-plus class="w-3.5 h-3.5" />
                    <span class="font-medium">Created By:</span>
                    ${call.creator?.full_name ?? '-'}
                </span>

                <span class="inline-flex items-center gap-1">
                    <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                    ${call.created_at}
                </span>

            </div>

        </div>

        <!-- Actions -->
        <div class="flex items-start gap-2 border-l border-gray-100 pl-3">

            <button
                onclick="viewCallNeeded(${call.id})"
                class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition">
                <x-heroicon-o-pencil-square class="w-5 h-5" />
            </button>

            <button
                onclick="clearCallNeeded(${call.id})"
                class="p-2 rounded-lg text-green-600 hover:bg-green-50 transition">
                <x-heroicon-o-check-circle class="w-5 h-5" />
            </button>

        </div>

    </div>

</div>
    `).join('');
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

       const customerRadio =
        document.querySelector('input[name="contact_type"][value="customer"]');

       const manualRadio =
            document.querySelector('input[name="contact_type"][value="manual"]');

        if (call.customer_id) {

            customerRadio.checked = true;

            document
                .getElementById('customer-section')
                .classList.remove('hidden');

            document
                .getElementById('manual-contact-section')
                .classList.add('hidden');

            window.callCustomerChoices.setChoiceByValue(
                String(call.customer_id)
            );

        } else {

            manualRadio.checked = true;

            document
                .getElementById('customer-section')
                .classList.add('hidden');

            document
                .getElementById('manual-contact-section')
                .classList.remove('hidden');

            document.getElementById('contact_name').value =
                call.contact_name ?? '';

            document.getElementById('contact_email').value =
                call.contact_email ?? '';

            document.getElementById('contact_phone').value =
                call.contact_phone ?? '';

            window.callCustomerChoices.removeActiveItems();
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
        }
    );
}

    loadCallNeededList();
});

document.addEventListener('change', function (e) {

    if (e.target.name !== 'contact_type') {
        return;
    }

    const isCustomer =
        e.target.value === 'customer';

    document
        .getElementById('customer-section')
        .classList.toggle('hidden', !isCustomer);

    document
        .getElementById('manual-contact-section')
        .classList.toggle('hidden', isCustomer);
});
</script>

@endpush
