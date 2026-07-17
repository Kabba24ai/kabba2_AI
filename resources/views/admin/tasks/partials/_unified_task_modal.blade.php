{{-- Unified New Task Modal — one frame for operational tasks and phone call reminders.
     The type toggle only swaps the type-specific fields/labels and which
     endpoint receives the payload; both save paths are unchanged. --}}
<div id="UnifiedTaskModal"
    style="display:none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">

    <div class="w-full mx-auto max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden max-h-[90vh] flex flex-col">

            {{-- Header --}}
            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">New Task</h2>
                    <p class="text-sm text-gray-500" id="ut_subtitle">Create an operational task</p>
                </div>
                <button type="button" onclick="closeNewTaskModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 px-6 pt-5 pb-4 space-y-5">

                {{-- Type toggle --}}
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-1" role="group">
                    <button type="button" id="ut_toggle_task" onclick="setUnifiedTaskMode('task')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition">
                        Operational task
                    </button>
                    <button type="button" id="ut_toggle_call" onclick="setUnifiedTaskMode('call')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition">
                        Phone call
                    </button>
                </div>

                {{-- Subject row (shared slot) --}}
                <div id="ut_subject_task">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                    <input type="text" id="ut_title" placeholder="What needs to be done?"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div id="ut_subject_call" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Call for <span class="text-red-500">*</span></label>
                    <select id="ut_reason" class="w-full">
                        <option value="">Select Reason</option>
                    </select>
                </div>

                {{-- Category | Priority --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="ut_category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                        <select id="ut_priority"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            @foreach ($priorities as $pri)
                                <option value="{{ $pri->value }}" {{ $pri->value === 'normal' ? 'selected' : '' }}>{{ $pri->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Related to (shared) --}}
                <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-semibold text-gray-700">
                            Related To
                            <span id="ut_related_optional" class="text-xs font-normal text-gray-400">(optional)</span>
                            <span id="ut_related_required" class="text-red-500 hidden">*</span>
                        </label>
                        <div class="flex items-center gap-5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="ut_related_type" value="customer" checked class="text-brand-600 focus:ring-brand-500">
                                <span class="text-sm text-gray-700">Customer</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="ut_related_type" value="supplier" class="text-brand-600 focus:ring-brand-500">
                                <span class="text-sm text-gray-700">Supplier</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="ut_related_type" value="other" class="text-brand-600 focus:ring-brand-500">
                                <span class="text-sm text-gray-700">Other</span>
                            </label>
                        </div>
                    </div>

                    {{-- Entity slot — one control, relabeled by the radio --}}
                    <div id="ut_customer_wrap">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Customer</label>
                        <select id="ut_customer" class="w-full">
                            <option value="">Select customer…</option>
                            @foreach ($customers as $customer)
                                @php
                                    $fullName = trim((string) $customer->full_name);
                                    $phone    = trim((string) $customer->phone);
                                @endphp
                                @if ($fullName || $phone)
                                    <option value="{{ $customer->id }}">{{ $fullName }}{{ $phone ? '    ·    ' . App\Helpers\CustomHelper::formatPhone($phone) : '' }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div id="ut_supplier_wrap" class="hidden">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Supplier</label>
                        <select id="ut_supplier" class="w-full">
                            <option value="">Select supplier…</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? '    ·    ' . \App\Helpers\CustomHelper::formatPhone($supplier->phone) : '' }}{{ $supplier->primary_contact_name ? '    ·    ' . $supplier->primary_contact_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="ut_other_wrap" class="hidden">
                        <div class="grid grid-cols-2 gap-3">
                            <div id="ut_other_name_col" class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Other</label>
                                <input type="text" id="ut_other_name" placeholder="Who or what is this about?"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            </div>
                            {{-- Phone number is only meaningful for a phone call --}}
                            <div id="ut_other_phone_col" class="hidden">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                <input type="text" id="ut_other_phone" placeholder="(xxx) xxx-xxxx"
                                    class="masked-phone w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            </div>
                        </div>
                    </div>

                    {{-- Equipment — operational only --}}
                    <div id="ut_equipment_block" class="pt-1">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Category</label>
                                <select id="ut_equip_category"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                    <option value="">All Categories</option>
                                    @foreach ($productCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Unit</label>
                                <select id="ut_equip_unit"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                    <option value="">Select Equipment</option>
                                </select>
                            </div>
                        </div>
                        <div id="ut_equip_summary" class="hidden text-xs text-gray-600 bg-white border border-gray-200 rounded px-3 py-2">
                            <span id="ut_equip_summary_text"></span>
                        </div>
                    </div>
                </div>

                {{-- Assign To | Due Date --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assign To <span id="ut_assign_required" class="text-red-500 hidden">*</span></label>
                        <select id="ut_assigned_to" class="w-full">
                            <option value="">Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name ?? $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <div class="relative">
                            <input type="text" id="ut_due_date" placeholder="Select date &amp; time" readonly
                                class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 cursor-pointer bg-white">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Status — operational only --}}
                <div id="ut_status_row">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="ut_status"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ $st->value === 'open' ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Description / Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" id="ut_notes_label">Description</label>
                    <textarea id="ut_notes" rows="3" placeholder="Optional details..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2 px-6 py-4 border-t flex-shrink-0">
                <button type="button" onclick="closeNewTaskModal()"
                    class="px-6 py-2.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="ut_save_btn" onclick="saveUnifiedTask()"
                    class="relative px-6 py-2.5 text-sm rounded-lg bg-brand-500 text-white flex items-center justify-center gap-2 hover:bg-brand-600">
                    <span id="ut_save_text">Create task</span>
                    <svg id="ut_save_spinner" xmlns="http://www.w3.org/2000/svg"
                        class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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
(function () {
    'use strict';

    var _utMode      = 'task';
    var _utFlatpickr = null;
    var _utEquipment = @json($equipmentList);

    // Reason lists per related-type. CALL_REASON_LISTS is defined by the shared
    // call modal partial (also on this page for edit/complete); fall back to a
    // minimal list if it's ever absent so the modal still functions.
    function utReasonList(type) {
        if (window.CALL_REASON_LISTS || typeof CALL_REASON_LISTS !== 'undefined') {
            var lists = window.CALL_REASON_LISTS || CALL_REASON_LISTS;
            return lists[type] || lists.customer;
        }
        return [{ value: 'general_followup', label: 'General Follow-up' }];
    }

    // ── Equipment helpers (same behavior as the old operational modal) ──────
    function utRebuildEquipment() {
        var catId    = document.getElementById('ut_equip_category').value;
        var filtered = catId
            ? _utEquipment.filter(function (e) { return e.category_id === parseInt(catId); })
            : _utEquipment;

        var sel = document.getElementById('ut_equip_unit');
        sel.innerHTML = '<option value="">Select Equipment</option>';
        filtered.forEach(function (e) {
            var opt = document.createElement('option');
            opt.value       = e.id;
            opt.textContent = e.equipment_id + ' — ' + e.name;
            sel.appendChild(opt);
        });
        utUpdateEquipSummary();
    }

    function utUpdateEquipSummary() {
        var id  = parseInt(document.getElementById('ut_equip_unit').value);
        var eq  = _utEquipment.find(function (e) { return e.id === id; });
        var box = document.getElementById('ut_equip_summary');
        if (eq) {
            var txt = '<strong>' + eq.equipment_id + '</strong> — ' + eq.name;
            if (eq.serial) txt += ' &nbsp;·&nbsp; S/N: ' + eq.serial;
            if (eq.status) txt += ' &nbsp;·&nbsp; Status: ' + eq.status;
            document.getElementById('ut_equip_summary_text').innerHTML = txt;
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }

    document.getElementById('ut_equip_category').addEventListener('change', utRebuildEquipment);
    document.getElementById('ut_equip_unit').addEventListener('change', utUpdateEquipSummary);
    utRebuildEquipment();

    // ── Flatpickr: datetime for operational, date-only for phone ───────────
    function utApplyPickerMode() {
        if (!_utFlatpickr) return;
        var isTask = _utMode === 'task';
        _utFlatpickr.set('enableTime', isTask);
        _utFlatpickr.set('dateFormat', isTask ? 'Y-m-d H:i:S' : 'Y-m-d');
        _utFlatpickr.set('altFormat',  isTask ? 'F j, Y h:i K' : 'F j, Y');
        _utFlatpickr.clear();
        // With altInput the visible field is the alt clone, not #ut_due_date
        var visibleInput = _utFlatpickr.altInput || document.getElementById('ut_due_date');
        visibleInput.placeholder = isTask ? 'Select date & time' : 'Select date';
    }

    function doInitUtPicker() {
        if (_utFlatpickr) return;
        _utFlatpickr = flatpickr('#ut_due_date', {
            enableTime:    true,
            dateFormat:    'Y-m-d H:i:S',
            altInput:      true,
            altFormat:     'F j, Y h:i K',
            minDate:       'today',
            time_24hr:     false,
            disableMobile: true,
            onReady: function (sel, str, instance) {
                instance.calendarContainer.style.zIndex = '200000';
            },
        });
    }

    if (window.flatpickr) {
        doInitUtPicker();
    } else {
        if (!document.getElementById('flatpickr-css')) {
            var link = document.createElement('link');
            link.id  = 'flatpickr-css'; link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(link);
        }
        var s    = document.createElement('script');
        s.src    = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
        s.onload = doInitUtPicker;
        document.head.appendChild(s);
    }

    // ── Choices selects ─────────────────────────────────────────────────────
    var utChoicesConfig = { searchEnabled: true, shouldSort: false, itemSelectText: '', searchResultLimit: 1000, renderChoiceLimit: -1 };

    document.addEventListener('DOMContentLoaded', function () {
        window.utCustomerChoices = new Choices(document.getElementById('ut_customer'), utChoicesConfig);
        window.utSupplierChoices = new Choices(document.getElementById('ut_supplier'), utChoicesConfig);
        window.utAssigneeChoices = new Choices(document.getElementById('ut_assigned_to'), utChoicesConfig);
        window.utReasonChoices   = new Choices(document.getElementById('ut_reason'), {
            searchEnabled: false, shouldSort: false, itemSelectText: '',
            placeholder: true, placeholderValue: 'Select Reason',
        });
        utSetReasonChoices('customer');
    });

    function utSetReasonChoices(type) {
        if (!window.utReasonChoices) return;
        window.utReasonChoices.setChoices(utReasonList(type), 'value', 'label', true);
    }

    function utRelatedType() {
        return document.querySelector('input[name="ut_related_type"]:checked').value;
    }

    // ── Mode toggle ─────────────────────────────────────────────────────────
    var TOGGLE_ACTIVE   = ['bg-green-100', 'text-green-700'];
    var TOGGLE_INACTIVE = ['text-gray-600'];

    window.setUnifiedTaskMode = function (mode) {
        _utMode = mode;
        var isTask  = mode === 'task';
        var taskBtn = document.getElementById('ut_toggle_task');
        var callBtn = document.getElementById('ut_toggle_call');

        taskBtn.classList.remove.apply(taskBtn.classList, TOGGLE_ACTIVE.concat(TOGGLE_INACTIVE));
        callBtn.classList.remove.apply(callBtn.classList, TOGGLE_ACTIVE.concat(TOGGLE_INACTIVE));
        (isTask ? taskBtn : callBtn).classList.add.apply((isTask ? taskBtn : callBtn).classList, TOGGLE_ACTIVE);
        (isTask ? callBtn : taskBtn).classList.add.apply((isTask ? callBtn : taskBtn).classList, TOGGLE_INACTIVE);

        document.getElementById('ut_subtitle').textContent =
            isTask ? 'Create an operational task' : 'Create a phone call reminder';

        document.getElementById('ut_subject_task').classList.toggle('hidden', !isTask);
        document.getElementById('ut_subject_call').classList.toggle('hidden', isTask);
        document.getElementById('ut_status_row').classList.toggle('hidden', !isTask);
        document.getElementById('ut_equipment_block').classList.toggle('hidden', !isTask);

        document.getElementById('ut_related_optional').classList.toggle('hidden', !isTask);
        document.getElementById('ut_related_required').classList.toggle('hidden', isTask);
        document.getElementById('ut_assign_required').classList.toggle('hidden', isTask);

        document.getElementById('ut_notes_label').textContent = isTask ? 'Description' : 'Notes';
        document.getElementById('ut_notes').placeholder = isTask ? 'Optional details...' : 'Enter call notes...';
        document.getElementById('ut_save_text').textContent = isTask ? 'Create task' : 'Save reminder';

        // Phone number for "Other" only makes sense on a phone call
        utApplyOtherPhoneVisibility();
        utApplyPickerMode();
    };

    function utApplyOtherPhoneVisibility() {
        var showPhone = _utMode === 'call' && utRelatedType() === 'other';
        document.getElementById('ut_other_phone_col').classList.toggle('hidden', !showPhone);
        document.getElementById('ut_other_name_col').classList.toggle('col-span-2', !showPhone);
    }

    // ── Related-to radio: one entity slot, relabeled per choice ────────────
    document.addEventListener('change', function (e) {
        if (e.target.name !== 'ut_related_type') return;
        var val = e.target.value;

        document.getElementById('ut_customer_wrap').classList.toggle('hidden', val !== 'customer');
        document.getElementById('ut_supplier_wrap').classList.toggle('hidden', val !== 'supplier');
        document.getElementById('ut_other_wrap').classList.toggle('hidden', val !== 'other');

        utSetReasonChoices(val === 'supplier' ? 'supplier' : 'customer');
        if (window.utReasonChoices) window.utReasonChoices.removeActiveItems();

        utApplyOtherPhoneVisibility();
    });

    // ── Modal open/close (same global names the Task Center already uses) ──
    window.openNewTaskModal = function () {
        document.getElementById('ut_title').value      = '';
        document.getElementById('ut_category').value   = '';
        document.getElementById('ut_priority').value   = 'normal';
        document.getElementById('ut_status').value     = 'open';
        document.getElementById('ut_notes').value      = '';
        document.getElementById('ut_other_name').value = '';
        document.getElementById('ut_other_phone').value = '';
        document.getElementById('ut_equip_category').value = '';
        utRebuildEquipment();

        document.querySelector('input[name="ut_related_type"][value="customer"]').checked = true;
        document.getElementById('ut_customer_wrap').classList.remove('hidden');
        document.getElementById('ut_supplier_wrap').classList.add('hidden');
        document.getElementById('ut_other_wrap').classList.add('hidden');

        if (window.utCustomerChoices) window.utCustomerChoices.removeActiveItems();
        if (window.utSupplierChoices) window.utSupplierChoices.removeActiveItems();
        if (window.utAssigneeChoices) window.utAssigneeChoices.removeActiveItems();
        utSetReasonChoices('customer');
        if (window.utReasonChoices) window.utReasonChoices.removeActiveItems();

        setUnifiedTaskMode('task');
        if (_utFlatpickr) _utFlatpickr.clear();
        else document.getElementById('ut_due_date').value = '';

        var modal = document.getElementById('UnifiedTaskModal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        setTimeout(function () { document.getElementById('ut_title').focus(); }, 100);
    };

    window.closeNewTaskModal = function () {
        var modal = document.getElementById('UnifiedTaskModal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
    };

    // ── Save: the toggle picks the payload + endpoint; both paths unchanged ─
    window.saveUnifiedTask = function () {
        if (_utMode === 'task') saveOperationalTask();
        else saveCallReminder();
    };

    function utBusy(on) {
        var btn = document.getElementById('ut_save_btn');
        btn.disabled = on;
        document.getElementById('ut_save_spinner').classList.toggle('hidden', !on);
    }

    function utRelatedSelections() {
        var type = utRelatedType();
        return {
            type:       type,
            customerId: type === 'customer' ? (document.getElementById('ut_customer').value || null) : null,
            supplierId: type === 'supplier' ? (document.getElementById('ut_supplier').value || null) : null,
            otherName:  type === 'other'    ? document.getElementById('ut_other_name').value.trim()  : '',
            otherPhone: type === 'other'    ? document.getElementById('ut_other_phone').value.trim() : '',
        };
    }

    function saveOperationalTask() {
        var category = document.getElementById('ut_category').value;
        var title    = document.getElementById('ut_title').value.trim();
        var related  = utRelatedSelections();

        if (!category) { notyf.error('Please select a category.'); return; }
        if (!title)    { notyf.error('Please enter a task.'); return; }

        utBusy(true);

        fetch("{{ route('admin.tasks.store') }}", {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                category:             category,
                title:                title,
                description:          document.getElementById('ut_notes').value.trim() || null,
                priority:             document.getElementById('ut_priority').value,
                status:               document.getElementById('ut_status').value,
                assigned_to_user_id:  document.getElementById('ut_assigned_to').value || null,
                related_equipment_id: document.getElementById('ut_equip_unit').value || null,
                related_customer_id:  related.customerId,
                related_supplier_id:  related.supplierId,
                related_other:        related.otherName || null,
                due_date:             document.getElementById('ut_due_date').value || null,
            }),
        })
        .then(utHandleResponse)
        .then(function (data) {
            if (!data.success) throw new Error(data.message || 'Could not create task.');
            notyf.success(data.message || 'Task created successfully.');
            closeNewTaskModal();
            window.location.reload();
        })
        .catch(function (err) { notyf.error(err.message); })
        .finally(function () { utBusy(false); });
    }

    function saveCallReminder() {
        var related    = utRelatedSelections();
        var reason     = document.getElementById('ut_reason').value;
        var category   = document.getElementById('ut_category').value;
        var assignedTo = document.getElementById('ut_assigned_to').value;

        if (related.type === 'customer' && !related.customerId) { notyf.error('Please select customer.'); return; }
        if (related.type === 'supplier' && !related.supplierId) { notyf.error('Please select a supplier.'); return; }
        if (related.type === 'other') {
            if (!related.otherName)  { notyf.error('Please enter name.');  return; }
            if (!related.otherPhone) { notyf.error('Please enter phone.'); return; }
        }
        if (!reason)     { notyf.error('Please select reason.'); return; }
        if (!category)   { notyf.error('Please select a category.'); return; }
        if (!assignedTo) { notyf.error('Please select assignee.'); return; }

        utBusy(true);

        fetch("{{ route('admin.dashboard.call-needed.store') }}", {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                customer_id:   related.customerId,
                supplier_id:   related.supplierId,
                contact_name:  related.type === 'other' ? related.otherName  : null,
                contact_email: null,
                contact_phone: related.type === 'other' ? related.otherPhone : null,
                assigned_to:   assignedTo,
                reason:        reason,
                category:      category,
                notes:         document.getElementById('ut_notes').value.trim(),
                priority:      document.getElementById('ut_priority').value,
                due_date:      document.getElementById('ut_due_date').value || null,
            }),
        })
        .then(utHandleResponse)
        .then(function (data) {
            if (!data.success) throw new Error(data.message || 'Could not save call reminder.');
            notyf.success(data.message || 'Call reminder created successfully.');
            closeNewTaskModal();
            window.location.reload();
        })
        .catch(function (err) { notyf.error(err.message); })
        .finally(function () { utBusy(false); });
    }

    function utHandleResponse(res) {
        if (res.status === 422) {
            return res.json().then(function (data) {
                var first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Validation error.');
                throw new Error(first);
            });
        }
        if (!res.ok) throw new Error('Server error (' + res.status + ').');
        return res.json();
    }

    // Close on backdrop click
    document.getElementById('UnifiedTaskModal').addEventListener('click', function (e) {
        if (e.target === this) closeNewTaskModal();
    });

}());
</script>
@endpush
