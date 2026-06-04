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
        <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden">

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

            {{-- Modal Body --}}
            {{ html()->form()
                    ->id('callNeededForm')
                    ->attributes([
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'px-6 py-5 space-y-4',
                    ])
                    ->open()
                }}

                <input type="hidden" id="call_needed_id" value="">

                {{-- Assign To --}}
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">
                        Customer
                    </label>

                    <select name="customer_id" id="call_customer_id"
                        class="choices-select w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        required>
                        <option value="">Select Customer</option>

                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->full_name }}
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
                        'rental_inquiry' => 'Rental Inquiry',
                        'equipment_availability' => 'Equipment Availability',
                        'delivery_pickup' => 'Delivery / Pickup',
                        'payment_followup' => 'Payment Follow-up',
                        'equipment_return' => 'Equipment Return',
                        'maintenance_request' => 'Maintenance Request',
                        'contract_renewal' => 'Contract Renewal',
                        'general_followup' => 'General Follow-up',
                    ], old('reason'))
                    ->id('call_reason')
                    ->class('choices-select w-full')
                    ->required()
                    !!}
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>

                   {!! html()->textarea('notes')
                        ->id('call_notes')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                        ->rows(4)
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
    document.getElementById('call_needed_id').value = '';

    document.getElementById('callModalTitle').innerText =
        'Add Call Reminder';

    document.getElementById('callBtnText').innerText =
        'Save';

    document.getElementById('callNeededForm').reset();

    if (window.callCustomerChoices) {
    window.callCustomerChoices.removeActiveItems();
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

        const customerId = document.getElementById('call_customer_id').value;
        const reason     = document.getElementById('call_reason').value;
        const notes      = document.getElementById('call_notes').value;

        if (!customerId) {
            notyf.error('Please select customer.');
            return;
        }

        if (!reason) {
            notyf.error('Please select reason.');
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
                customer_id: customerId,
                reason: reason,
                notes: notes,
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
        <div class="border border-red-100 rounded-xl p-4">

            <div class="flex justify-between">

                <div>

                    <h3 class="text-sm font-semibold text-gray-900">
                        ${call.customer.full_name}
                    </h3>

                    <p class="text-sm text-gray-600 mt-1">
                        ${formatReason(call.reason)}
                    </p>

                    <p class="text-xs text-gray-400 mt-2">
                        ${call.created_at}

                        
                    </p>

                </div>

                <div class="flex gap-2">

                   

                    <button   onclick="viewCallNeeded(${call.id})" class="text-blue-600 hover:text-blue-800" title="View">
                            <x-heroicon-o-eye class="w-5 h-5" />
                        </button>

                          <button    onclick="clearCallNeeded(${call.id})" class="text-red-600 hover:text-red-800">
                             <x-heroicon-o-trash class="w-5 h-5" />
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

       window.callCustomerChoices.setChoiceByValue(
            String(call.customer_id)
        );

        window.callReasonChoices.setChoiceByValue(
            call.reason
        );

        document.getElementById('call_notes').value =
            call.notes ?? '';

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
</script>

@endpush
