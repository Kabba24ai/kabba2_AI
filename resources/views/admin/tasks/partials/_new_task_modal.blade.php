{{-- New Task Modal --}}
<div id="NewTaskModal"
    style="display:none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">

    <div class="w-full mx-auto max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden max-h-[90vh] flex flex-col">

            {{-- Header --}}
            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">New Task</h2>
                    <p class="text-sm text-gray-500">Create a new operational task</p>
                </div>
                <button type="button" onclick="closeNewTaskModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 px-6 pt-5 pb-4 space-y-5">

                {{-- Category | Title --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Task Category <span class="text-red-500">*</span></label>
                        <select id="nt_category" name="category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="nt_title" placeholder="What needs to be done?"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>

                {{-- Equipment --}}
                <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Assign Equipment <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Category</label>
                            <select id="nt_equip_category"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <option value="">All Categories</option>
                                @foreach ($productCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Unit</label>
                            <select id="nt_equip_unit"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <option value="">Select Equipment</option>
                            </select>
                        </div>
                    </div>
                    <div id="nt_equip_summary" class="hidden text-xs text-gray-600 bg-white border border-gray-200 rounded px-3 py-2">
                        <span id="nt_equip_summary_text"></span>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="nt_description" rows="3" placeholder="Optional details..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>

                {{-- Priority | Status --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                        <select id="nt_priority"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            @foreach ($priorities as $pri)
                                <option value="{{ $pri->value }}" {{ $pri->value === 'normal' ? 'selected' : '' }}>{{ $pri->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="nt_status"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}" {{ $st->value === 'open' ? 'selected' : '' }}>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Assigned To | Due Date --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                        <select id="nt_assigned_to"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                            <option value="">Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <div class="relative">
                            <input type="text" id="nt_due_date" placeholder="Select date &amp; time" readonly
                                class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 cursor-pointer bg-white">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2 px-6 py-4 border-t flex-shrink-0">
                <button type="button" onclick="closeNewTaskModal()"
                    class="px-6 py-2.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="new-task-save-btn" onclick="saveNewTask()"
                    class="relative px-6 py-2.5 text-sm rounded-lg bg-brand-500 text-white flex items-center justify-center gap-2 hover:bg-brand-600">
                    <span id="newTaskBtnText">Create Task</span>
                    <svg id="newTaskBtnSpinner" xmlns="http://www.w3.org/2000/svg"
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

    var _ntFlatpickr   = null;
    var _ntEquipment   = @json($equipmentList);

    // ── Equipment helpers ──────────────────────────────────────────────────
    function ntRebuildEquipment() {
        var catId    = document.getElementById('nt_equip_category').value;
        var filtered = catId
            ? _ntEquipment.filter(function (e) { return e.category_id === parseInt(catId); })
            : _ntEquipment;

        var sel = document.getElementById('nt_equip_unit');
        sel.innerHTML = '<option value="">Select Equipment</option>';
        filtered.forEach(function (e) {
            var opt = document.createElement('option');
            opt.value       = e.id;
            opt.textContent = e.equipment_id + ' — ' + e.name;
            sel.appendChild(opt);
        });
        ntUpdateEquipSummary();
    }

    function ntUpdateEquipSummary() {
        var id  = parseInt(document.getElementById('nt_equip_unit').value);
        var eq  = _ntEquipment.find(function (e) { return e.id === id; });
        var box = document.getElementById('nt_equip_summary');
        if (eq) {
            var txt = '<strong>' + eq.equipment_id + '</strong> — ' + eq.name;
            if (eq.serial) txt += ' &nbsp;·&nbsp; S/N: ' + eq.serial;
            if (eq.status) txt += ' &nbsp;·&nbsp; Status: ' + eq.status;
            document.getElementById('nt_equip_summary_text').innerHTML = txt;
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }

    document.getElementById('nt_equip_category').addEventListener('change', ntRebuildEquipment);
    document.getElementById('nt_equip_unit').addEventListener('change', ntUpdateEquipSummary);
    ntRebuildEquipment();

    // ── Flatpickr ──────────────────────────────────────────────────────────
    function doInitNtPicker() {
        if (_ntFlatpickr) return;
        _ntFlatpickr = flatpickr('#nt_due_date', {
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
        doInitNtPicker();
    } else {
        if (!document.getElementById('flatpickr-css')) {
            var link = document.createElement('link');
            link.id  = 'flatpickr-css'; link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(link);
        }
        var s    = document.createElement('script');
        s.src    = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
        s.onload = doInitNtPicker;
        document.head.appendChild(s);
    }

    // ── Modal open/close ───────────────────────────────────────────────────
    window.openNewTaskModal = function () {
        document.getElementById('nt_category').value     = '';
        document.getElementById('nt_title').value        = '';
        document.getElementById('nt_description').value  = '';
        document.getElementById('nt_priority').value     = 'normal';
        document.getElementById('nt_status').value       = 'open';
        document.getElementById('nt_assigned_to').value  = '';
        document.getElementById('nt_equip_category').value = '';
        ntRebuildEquipment();
        if (_ntFlatpickr) _ntFlatpickr.clear();
        else document.getElementById('nt_due_date').value = '';

        var modal = document.getElementById('NewTaskModal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        setTimeout(function () { document.getElementById('nt_title').focus(); }, 100);
    };

    window.closeNewTaskModal = function () {
        var modal = document.getElementById('NewTaskModal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
    };

    // ── Save ───────────────────────────────────────────────────────────────
    window.saveNewTask = function () {
        var category    = document.getElementById('nt_category').value;
        var title       = document.getElementById('nt_title').value.trim();
        var description = document.getElementById('nt_description').value.trim();
        var priority    = document.getElementById('nt_priority').value;
        var status      = document.getElementById('nt_status').value;
        var assignedTo  = document.getElementById('nt_assigned_to').value || null;
        var equipId     = document.getElementById('nt_equip_unit').value || null;
        var dueDate     = document.getElementById('nt_due_date').value || null;

        if (!category) { notyf.error('Please select a task category.'); return; }
        if (!title)    { notyf.error('Please enter a title.'); return; }

        var btn     = document.getElementById('new-task-save-btn');
        var btnText = document.getElementById('newTaskBtnText');
        var spinner = document.getElementById('newTaskBtnSpinner');
        btn.disabled = true;
        btnText.textContent = 'Saving...';
        spinner.classList.remove('hidden');

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
                description:          description || null,
                priority:             priority,
                status:               status,
                assigned_to_user_id:  assignedTo,
                related_equipment_id: equipId,
                due_date:             dueDate,
            }),
        })
        .then(function (res) {
            if (res.status === 422) {
                return res.json().then(function (data) {
                    var first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Validation error.');
                    throw new Error(first);
                });
            }
            if (!res.ok) throw new Error('Server error (' + res.status + ').');
            return res.json();
        })
        .then(function (data) {
            if (!data.success) throw new Error(data.message || 'Could not create task.');
            notyf.success(data.message || 'Task created successfully.');
            closeNewTaskModal();
            window.location.reload();
        })
        .catch(function (err) {
            notyf.error(err.message);
        })
        .finally(function () {
            btn.disabled = false;
            btnText.textContent = 'Create Task';
            spinner.classList.add('hidden');
        });
    };

    // Close on backdrop click
    document.getElementById('NewTaskModal').addEventListener('click', function (e) {
        if (e.target === this) closeNewTaskModal();
    });

}());
</script>
@endpush
