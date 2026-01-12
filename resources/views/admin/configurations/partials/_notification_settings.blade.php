{{-- Notification Settings --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-5">
   
   <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="grid grid-cols-1 gap-3">
         <div class="space-y-3 bg-blue-50 p-4 rounded-xl border border-blue-200">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-3">
                        <x-heroicon-o-bell class="h-5 w-5 text-blue-600" />

                  <h2 class="text-lg font-semibold text-slate-800"> New Order Notification</h2>
               </div>
              <div class="flex gap-2">
                <button
                    type="button"
                    data-add-hrm="order"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg"
                >
                    Add HRM Users
                </button>

                <button
                    type="button"
                    data-edit="order"
                    class="px-4 py-2 bg-white border rounded-lg text-blue-600"
                >
                    Add Manual
                </button>
            </div>


            </div>
            <div id="view-table-order" class="space-y-4">
                @include(
                            'admin.configurations.partials._notification_view_table',
                            ['rows' => $newOrderRows]
                        )
              
            </div>
         </div>
      </div>
   </div>
   <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="grid grid-cols-1 gap-3">
         <div class="space-y-3 bg-green-50 p-4 rounded-xl border border-green-200">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-3">
                        <x-heroicon-o-bell class="h-5 w-5 text-blue-600" />

                  <h2 class="text-lg font-semibold text-slate-800">Emergency Services Notification</h2>
               </div>
            

               <!-- <div class="flex gap-2">
                    <button
                        type="button"
                        data-add-hrm="emergency"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg"
                    >
                        Add HRM Users
                    </button>

                    <button
                        type="button"
                        data-edit="emergency"
                        class="px-4 py-2 bg-white border rounded-lg text-blue-600"
                    >
                        Add Manual
                    </button>
                </div> -->

            </div>
            <div>Coming Soon</div>
            <?php /*<div id="view-table-emergency" class="space-y-4">
                @include(
                            'admin.configurations.partials._notification_view_table',
                            ['rows' => $emergencyRows]
                        )
               
            </div>*/?>
         </div>
      </div>
   </div>
   
</div>


<div
    id="manual-modal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10"
>
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">

        <h3 class="text-lg font-semibold">
            Add Manual Recipient
        </h3>

        <div>
            <label class="text-sm font-medium">Name</label>
            <input
                id="manual-name"
                type="text"
                class="w-full border rounded-md px-3 py-2"
                placeholder="Person / Description"
            />
        </div>

        <div>
            <label class="text-sm font-medium">Phone</label>
            <input
                id="manual-phone"
                type="text"
                class="masked-phone w-full border rounded-md px-3 py-2"
                placeholder="(xxx) xxx-xxxx"
            />
        </div>

        <div class="flex justify-end gap-2 pt-3">
            <button
                type="button"
                id="cancel-manual"
                class="px-4 py-2 border rounded-lg"
            >
                Cancel
            </button>

            <button
                type="button"
                id="save-manual"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg"
            >
                Save
            </button>
        </div>
    </div>
</div>



    @include('admin.configurations.partials._notification_edit_table')
@include('admin.configurations.partials._notification_hrm_modal')


@push('js')
<script>

   
window.notificationData = {
    order: @json($newOrderRows),
    emergency: @json($emergencyRows),
};

const PHONE_REGEX = /^\(\d{3}\)\s\d{3}-\d{4}$/;

function validateRows() {
    let isValid = true;
    let firstInvalidInput = null;

    // remove previous errors
    document.querySelectorAll('.phone-error').forEach(el => el.remove());

    document.querySelectorAll('#edit-table-body tr').forEach((tr, index) => {
        const phoneInput = tr.querySelector('.masked-phone');

        if (!phoneInput) return;

        const value = phoneInput.value.trim();

        //  empty
        if (!value) {
            showPhoneError(phoneInput, 'Phone number is required');
            isValid = false;
        }
        //  invalid format
        else if (!PHONE_REGEX.test(value)) {
            showPhoneError(
                phoneInput,
                'Please enter phone number in format (xxx) xxx-xxxx'
            );
            isValid = false;
        }

        if (!firstInvalidInput && !isValid) {
            firstInvalidInput = phoneInput;
        }
    });

    if (!isValid && firstInvalidInput) {
        firstInvalidInput.focus();
        notyf.error('Please fix invalid phone numbers before saving');
    }

    return isValid;
}
function showPhoneError(input, message) {
    // Add parsley error class
    input.classList.add('parsley-error');

    // Remove existing error list (if any)
    const existing = input
        .closest('td')
        .querySelector('.parsley-errors-list');

    if (existing) existing.remove();

    // Create Parsley error list
    const ul = document.createElement('ul');
    ul.className = 'parsley-errors-list filled';
    ul.setAttribute('aria-hidden', 'false');

    const li = document.createElement('li');
    li.className = 'parsley-custom-error-message';
    li.innerHTML = message;

    ul.appendChild(li);

    // Append right under input
    input.closest('td').appendChild(ul);

    // Remove error on user input
    input.addEventListener(
        'input',
        () => {
            input.classList.remove('parsley-error');
            ul.remove();
        },
        { once: true }
    );
}


function initPhoneMask(context = document) {
    const phoneInputs = context.querySelectorAll('.masked-phone');

    phoneInputs.forEach(input => {
        if (input.dataset.imaskApplied) return; // prevent duplicate mask

        IMask(input, {
            mask: '(000) 000-0000'
        });

        input.dataset.imaskApplied = 'true';
    });
}


document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('notification-modal');
    const modalTitle = document.getElementById('modal-title');
    const tbody = document.getElementById('edit-table-body');

    const state = {
        type: null,
        rows: [],
        hrmUsers: [],
    };

    function fetchHrmUsers() {
        return fetch("{{ route('admin.configurations.hrm-users.index') }}?ajax=1", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            // IMPORTANT: adapt keys to your API response
            state.hrmUsers = data.users || data.data || [];
        });
    }

    function openModalWithData() {

        fetchHrmUsers()
        .then(() => {

            //  LOAD SAVED DATA INSTEAD OF MOCK
            state.rows = JSON.parse(
                JSON.stringify(window.notificationData[state.type] || [])
            );

            // If empty, add one default row
            if (!state.rows.length) {
                state.rows.push({
                    source: 'manual',
                    name: '',
                    phone: ''
                });
            }

            renderRows();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        })
        .catch(err => {
            console.error(err);
            notyf.error('Failed to load HRM users');
        });

            renderRows();
        initPhoneMask(modal);

    }

    const manualModal = document.getElementById('manual-modal');
    const manualName = document.getElementById('manual-name');
    const manualPhone = document.getElementById('manual-phone');

    document.querySelectorAll('[data-edit]').forEach(btn => {
        btn.addEventListener('click', () => {
            state.type = btn.dataset.edit;

            
            manualName.value = '';
            manualPhone.value = '';

            manualModal.classList.remove('hidden');
            manualModal.classList.add('flex');

            initPhoneMask(manualModal);
        });
    });

document.getElementById('cancel-manual').onclick = () => {
    manualModal.classList.add('hidden');
    manualModal.classList.remove('flex');
};

document.getElementById('save-manual').onclick = () => {
    const name = manualName.value.trim();
    const phone = manualPhone.value.trim();

    if (!name || !PHONE_REGEX.test(phone)) {
        notyf.error('Enter valid name and phone');
        return;
    }

    const updated = [
        {
            source: 'manual',
            user_id: 0,
            name,
            phone
        },
        // keep HRM users
        ...(window.notificationData[state.type] || []).filter(r => r.source === 'hrm')
    ];

    window.notificationData[state.type] = updated;

    updateView(state.type);

    manualModal.classList.add('hidden');
    manualModal.classList.remove('flex');
};


    // CLOSE MODAL
    document.getElementById('close-modal').onclick =
    document.getElementById('cancel-modal').onclick = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // ADD HRM
    document.getElementById('add-hrm').onclick = () => {
        state.rows.push({ source: 'hrm', user_id: '', name: '', phone: '' });
        renderRows();
    };

    // ADD MANUAL
            document.getElementById('add-manual').onclick = () => {

            // Check if manual already exists
            const hasManual = state.rows.some(r => r.source === 'manual');

            if (hasManual) {
                notyf.error('Only one manual recipient is allowed');
                return;
            }

            state.rows.push({
                source: 'manual',
                name: '',
                phone: ''
            });

            renderRows();
        };

            // SAVE
        document.getElementById('save-modal').onclick = () => {
            if (!validateRows()) return;

            const existing = window.notificationData[state.type] || [];

            const hrmUsers = existing.filter(r => r.source === 'hrm');
            const updatedManual = state.rows[0]; // only one

            const finalRows = [
                updatedManual,
                ...hrmUsers
            ];

            fetch("{{ route('admin.configurations.notification-settings.save') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    type: state.type,
                    recipients: finalRows
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    notyf.error(data.message);
                    return;
                }

                // update cache
                window.notificationData[state.type] = finalRows;

                const target = document.getElementById(
                    state.type === 'order'
                        ? 'view-table-order'
                        : 'view-table-emergency'
                );

                target.innerHTML = data.html;

                notyf.success('Manual recipient updated');
                closeModal();
            });
        };


        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            // restore buttons
            document.getElementById('add-hrm').style.display = '';
            document.getElementById('add-manual').style.display = '';

            state.rows = [];
        }


        document.getElementById('close-modal').onclick =
        document.getElementById('cancel-modal').onclick = closeModal;


    // RENDER TABLE
    function renderRows() {
        tbody.innerHTML = '';

        state.rows.forEach((row, index) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td class="px-3 py-2">
                    ${
                        row.source === 'hrm'
                        ? hrmSelect(row, index)
                        : `<input
                                class="w-full border rounded-md px-2 py-1"
                                placeholder="Enter User Name"
                                value="${row.name || ''}"
                                data-name="${index}"
                        />`

                    }
                </td>
                <td class="px-3 py-2">
                <input
                    class="masked-phone w-full border rounded-md px-2 py-1"
                    placeholder="(xxx) xxx-xxxx"
                    value="${row.phone || ''}"
                    data-phone="${index}"
                />

                </td>
                <td class="px-3 py-2 text-right">
                    <button type="button" data-delete="${index}" class="text-red-600"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                    </svg></button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        bindRowEvents();

        //  THIS LINE WAS MISSING
        initPhoneMask(tbody);
    }

    function hrmSelect(row, index) {
        return `
            <select data-hrm="${index}" class="w-full border rounded-md px-2 py-1">
                <option value="">Select user</option>
                ${state.hrmUsers.map(u =>
                    `<option value="${u.id}" ${u.id == row.user_id ? 'selected' : ''}>${u.name}</option>`
                ).join('')}
            </select>
        `;
    }

    function bindRowEvents() {

        // NAME INPUT (manual)
            document.querySelectorAll('[data-name]').forEach(input => {
                input.oninput = () => {
                    state.rows[input.dataset.name].name = input.value;
                };
            });

            // PHONE INPUT (all rows)
            document.querySelectorAll('[data-phone]').forEach(input => {
                input.oninput = () => {
                    state.rows[input.dataset.phone].phone = input.value;
                };
            });


        document.querySelectorAll('[data-delete]').forEach(btn => {
            btn.onclick = () => {
                state.rows.splice(btn.dataset.delete, 1);
                renderRows();
            };
        });

        document.querySelectorAll('[data-hrm]').forEach(select => {
            select.onchange = () => {
                const row = state.rows[select.dataset.hrm];
                const user = state.hrmUsers.find(u => u.id == select.value);
                if (user) {
                    row.user_id = user.id;
                    row.name = user.name;
                    row.phone = user.phone;
                    renderRows();
                }
            };
        });
    }

    const hrmModal = document.getElementById('hrm-modal');
    const hrmList = document.getElementById('hrm-user-list');

    let hrmSelectedType = null;

    document.querySelectorAll('[data-add-hrm]').forEach(btn => {
        btn.addEventListener('click', () => {
            hrmSelectedType = btn.dataset.addHrm;
            openHrmModal();
        });
    });

    function openHrmModal() {
        fetchHrmUsers().then(() => {
            renderHrmUsers();
            hrmModal.classList.remove('hidden');
            hrmModal.classList.add('flex');
        });
    }

    function renderHrmUsers() {
        hrmList.innerHTML = '';

        const existingIds = (window.notificationData[hrmSelectedType] || [])
            .filter(r => r.source === 'hrm')
            .map(r => String(r.user_id));

        state.hrmUsers.forEach(user => {
            const isAdded = existingIds.includes(String(user.id));

            const div = document.createElement('div');
            div.className =
                'flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50';

            div.innerHTML = `
                <input
                    type="checkbox"
                    class="hrm-checkbox"
                    ${isAdded ? 'checked ' : ''}
                    data-hrm-id="${user.id}"
                    data-hrm-name="${user.name}"
                    data-hrm-phone="${user.phone}"
                />
                <div>
                    <div class="font-medium">${user.name}</div>
                    <div class="text-sm text-gray-500">${user.phone}</div>
                </div>
            `;

            const checkbox = div.querySelector('.hrm-checkbox');

            // click anywhere in row toggles checkbox
            div.addEventListener('click', (e) => {
                // prevent double toggle when clicking checkbox itself
                if (e.target.tagName === 'INPUT') return;

                if (checkbox.disabled) return;

                checkbox.checked = !checkbox.checked;
            });

            hrmList.appendChild(div);
        });
    }

    document.getElementById('save-hrm-modal').onclick = () => {
        const updated = [];

        document
            .querySelectorAll('#hrm-user-list input[type="checkbox"]:checked')
            .forEach(cb => {
                updated.push({
                    source: 'hrm',
                    user_id: cb.dataset.hrmId,
                    name: cb.dataset.hrmName,
                    phone: cb.dataset.hrmPhone,
                });
            });

        // Preserve manual user if exists
        const manual = (window.notificationData[hrmSelectedType] || [])
            .find(r => r.source === 'manual');

        if (manual) {
            updated.push(manual);
        }

        window.notificationData[hrmSelectedType] = updated;

        updateView(hrmSelectedType);
        closeHrmModal();
    };

    function updateView(type, recipients = null) {
    const payload = recipients ?? window.notificationData[type];

    fetch("{{ route('admin.configurations.notification-settings.save') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            type,
            recipients: payload
        })
    })
    .then(r => r.json())
    .then(d => {
        const container = document.getElementById(
            type === 'order' ? 'view-table-order' : 'view-table-emergency'
        );

        container.innerHTML = d.html;

        //  CRITICAL: refresh cache
        window.notificationData[type] = payload;

        // CRITICAL: re-init mask after DOM replace
        initPhoneMask(container);

        notyf.success(d.message || 'Notification settings updated');
    });
}




    function closeHrmModal() {
        hrmModal.classList.add('hidden');
        hrmModal.classList.remove('flex');
    }

    document.getElementById('close-hrm-modal').onclick =
    document.getElementById('cancel-hrm-modal').onclick = closeHrmModal;

   document.addEventListener('click', e => {
    const btn = e.target.closest('.data-update-manual');

    if (!btn) return;


    const index = Number(btn.dataset.updateManual);
    const type  = btn.dataset.type;

   

    if (Number.isNaN(index) || !type) {
        
        return;
    }

    const nameInput  = document.querySelector(`[data-manual-name="${index}"]`);
    const phoneInput = document.querySelector(`[data-manual-phone="${index}"]`);

    // console.log('Name input:', nameInput);
    // console.log('Phone input:', phoneInput);

    if (!nameInput || !phoneInput) {
        notyf.error('Unable to locate manual inputs');
        // console.groupEnd();
        return;
    }

    nameInput.focus();

    const save = () => {
        const name  = nameInput.value.trim();
        const phone = phoneInput.value.trim();
const manualId = btn.dataset.id;
        // console.log('Saving values:', { name, phone });

        if (!name || !PHONE_REGEX.test(phone)) {
            notyf.error('Invalid name or phone number');
            return;
        }

        const payload = [
            {
                 id: manualId,      
                source: 'manual',
                user_id: 0,
                name,
                phone
            },
            ...(window.notificationData[type] || []).filter(r => r.source === 'hrm')
        ];

        // console.log('Payload to save:', payload);

        updateView(type, payload);
    };

    // save on Enter
    phoneInput.onkeydown = ev => {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            save();
        }
    };

    // save on blur (IMPORTANT)
    nameInput.onblur  = save;
    phoneInput.onblur = save;

    // console.groupEnd();
});






    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-delete]');
        if (!btn) return;

        const id = btn.dataset.id;
        const type = btn.dataset.type;

        window.showConfirm(
            'Are you sure you want to delete this recipient?',
            'Delete Recipient'
        ).then(result => {
            if (!result.isConfirmed) return;

            fetch(
                "{{ route('admin.configurations.notification-settings.delete', '__ID__') }}"
                    .replace('__ID__', id),
                {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                }
            )
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    notyf.error(data.message || 'Delete failed');
                    return;
                }

                // update UI section
                const target = document.getElementById(
                    type === 'order' ? 'view-table-order' : 'view-table-emergency'
                );

                target.innerHTML = data.html;

                // also update cache if you want
                window.notificationData[type] = window.notificationData[type].filter(r => r.id != id);

                notyf.success(data.message || 'Deleted');
            })
            .catch(() => notyf.error('Something went wrong'));
        });
    });

});

</script>
@endpush