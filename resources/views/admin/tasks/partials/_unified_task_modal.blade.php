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
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-md transition">
                        <x-heroicon-o-check-circle class="w-4 h-4" />
                        Operational task
                    </button>
                    <button type="button" id="ut_toggle_call" onclick="setUnifiedTaskMode('call')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-md transition">
                        <x-heroicon-o-phone class="w-4 h-4" />
                        Phone call
                    </button>
                </div>

                {{-- Subject row (shared slot) --}}
                <div id="ut_subject_task">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
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
                        {{-- Start-by-order: global order search. Selecting an order fills
                             the customer and the dependent Customer Order dropdown below. --}}
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Search by Order # <span class="font-normal text-gray-400">(optional)</span></label>
                            <div class="relative">
                                <input type="text" id="ut_order_search" autocomplete="off" placeholder="e.g. 3151"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <div id="ut_order_results"
                                    class="hidden absolute z-[100000] mt-1 w-full max-h-56 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"></div>
                            </div>
                            <p id="ut_order_msg" class="hidden mt-1 text-xs"></p>
                        </div>

                        {{-- Customer + dependent Customer Order dropdown. The order list
                             is scoped to the selected customer; picking one is optional. --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
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
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Customer Order <span class="font-normal text-gray-400">(optional)</span></label>
                                <select id="ut_customer_order" class="w-full" disabled>
                                    <option value="">Select a customer first</option>
                                </select>
                            </div>
                        </div>
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

                {{-- New tasks always start Open (enforced server-side); there is
                     no Status field at creation. Move through statuses on the
                     Task Detail page as work progresses. --}}

                {{-- Description / Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" id="ut_notes_label">Description</label>
                    <textarea id="ut_notes" rows="3" placeholder="Optional details..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>

                {{-- Attachments — operational only --}}
                <div id="ut_media_block">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Attachments <span class="text-xs font-normal text-gray-400">(images / video, optional)</span>
                    </label>
                    <input type="file" id="ut_media" multiple
                        accept="image/*,video/mp4,video/quicktime,video/x-msvideo,video/webm"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer bg-white
                               file:mr-3 file:py-2 file:px-3 file:border-0 file:rounded-l-md file:bg-gray-100 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                    <p class="text-xs text-gray-400 mt-1">Up to 10 files, 50&nbsp;MB each. Attachments are removed 30 days after the task is completed.</p>
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
@include('admin.dashboard.partials._call_reason_lists')
<script>
(function () {
    'use strict';

    var _utMode      = 'task';
    var _utFlatpickr = null;
    var _utEquipment = @json($equipmentList);

    // Order link state — shared by both save paths. {id, order_number,
    // customer_id, customer_name} from the charge-modal lookup endpoint.
    var _utOrder      = null;
    var _utOrderSeq   = 0;    // stale async lookup guard
    var _utOrderTimer = null;
    var UT_ORDER_LOOKUP_URL = "{{ route('admin.dashboard.charge-modal.orders') }}";

    // Dependent Customer → Order dropdown state.
    var _utCustomerOrders     = [];    // orders loaded for the selected customer
    var _utCustOrdSeq         = 0;     // stale async guard for the dependent list
    var _utSyncingCustomer    = false; // suppress the customer-change reload during programmatic sync
    var UT_CUSTOMER_ORDERS_URL = "{{ route('admin.dashboard.charge-modal.customer-orders') }}";

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
        window.utCustomerOrderChoices = new Choices(document.getElementById('ut_customer_order'), utChoicesConfig);
        utResetCustomerOrder();
        utSetReasonChoices('customer');

        // Dependent Customer → Order wiring.
        document.getElementById('ut_customer').addEventListener('change', utOnCustomerChange);
        document.getElementById('ut_customer_order').addEventListener('change', utOnCustomerOrderChange);
    });

    function utSetReasonChoices(type) {
        if (!window.utReasonChoices) return;
        window.utReasonChoices.setChoices(utReasonList(type), 'value', 'label', true);
    }

    function utRelatedType() {
        return document.querySelector('input[name="ut_related_type"]:checked').value;
    }

    // ── Order lookup (reuses the shared charge-modal orders endpoint) ───────
    function utOrderMsg(text, tone) {
        var el = document.getElementById('ut_order_msg');
        el.classList.remove('text-red-600', 'text-amber-600', 'text-gray-500');
        if (!text) { el.classList.add('hidden'); el.textContent = ''; return; }
        el.textContent = text;
        el.classList.add(tone === 'error' ? 'text-red-600' : tone === 'warn' ? 'text-amber-600' : 'text-gray-500');
        el.classList.remove('hidden');
    }

    function utHideOrderResults() {
        var box = document.getElementById('ut_order_results');
        box.classList.add('hidden');
        box.innerHTML = '';
    }

    // ── Dependent Customer → Order dropdown ─────────────────────────────────
    function utFormatCustomerOrderLabel(o) {
        var label = '#' + o.order_number + ' — ' + (o.first_product || 'Order');
        if (o.extra_count > 0) label += ' +' + o.extra_count + ' more';
        if (o.order_date)      label += ' — ' + o.order_date;
        return label;
    }

    // Disabled "Select a customer first" state; forgets any loaded orders.
    function utResetCustomerOrder() {
        _utCustomerOrders = [];
        if (!window.utCustomerOrderChoices) return;
        window.utCustomerOrderChoices.clearStore();
        window.utCustomerOrderChoices.setChoices(
            [{ value: '', label: 'Select a customer first', selected: true, placeholder: true }],
            'value', 'label', true
        );
        window.utCustomerOrderChoices.disable();
    }

    function utFindLoadedOrder(id) {
        id = String(id);
        return _utCustomerOrders.find(function (o) { return String(o.id) === id; }) || null;
    }

    function utPopulateCustomerOrderChoices(orders, selectId) {
        if (!window.utCustomerOrderChoices) return;
        var choices = [{ value: '', label: 'Select an order', placeholder: true, selected: !selectId }];
        orders.forEach(function (o) {
            choices.push({
                value:    String(o.id),
                label:    utFormatCustomerOrderLabel(o),
                selected: selectId && String(o.id) === String(selectId),
            });
        });
        window.utCustomerOrderChoices.clearStore();
        window.utCustomerOrderChoices.setChoices(choices, 'value', 'label', true);
        window.utCustomerOrderChoices.enable();
    }

    // Load a customer's orders into the dependent dropdown. `ensureOrder` (from a
    // global search / prefill) is appended if the fetched list omits it, and
    // `selectId` is preselected when present.
    function utLoadCustomerOrders(customerId, selectId, ensureOrder) {
        if (!customerId) { utResetCustomerOrder(); return Promise.resolve(); }

        var seq = ++_utCustOrdSeq;
        if (window.utCustomerOrderChoices) {
            window.utCustomerOrderChoices.clearStore();
            window.utCustomerOrderChoices.setChoices(
                [{ value: '', label: 'Loading orders…', selected: true, placeholder: true }],
                'value', 'label', true
            );
            window.utCustomerOrderChoices.disable();
        }

        return fetch(UT_CUSTOMER_ORDERS_URL + '?customer_id=' + encodeURIComponent(customerId), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (seq !== _utCustOrdSeq) return; // a newer customer change superseded this
                var orders = data.results || [];
                if (ensureOrder && !orders.some(function (o) { return String(o.id) === String(ensureOrder.id); })) {
                    orders = [ensureOrder].concat(orders);
                }
                _utCustomerOrders = orders;
                utPopulateCustomerOrderChoices(orders, selectId);
            })
            .catch(function () {
                if (seq === _utCustOrdSeq) utResetCustomerOrder();
            });
    }

    // Customer changed → (re)load its orders; drop any now-incompatible order.
    function utOnCustomerChange() {
        if (_utSyncingCustomer) return;
        var customerId = document.getElementById('ut_customer').value;
        if (!customerId) {
            _utOrder = null;
            utResetCustomerOrder();
            return;
        }
        if (_utOrder && String(_utOrder.customer_id) !== String(customerId)) {
            _utOrder = null;
        }
        utLoadCustomerOrders(customerId, _utOrder ? _utOrder.id : null);
    }

    function utOnCustomerOrderChange() {
        var id = document.getElementById('ut_customer_order').value;
        _utOrder = id ? utFindLoadedOrder(id) : null;
    }

    // Select a customer in the Choices dropdown, appending the option first
    // when the customer isn't in the preloaded Active/Archived list.
    function utSelectCustomer(id, name) {
        var sel = document.getElementById('ut_customer');
        if (!id) {
            if (window.utCustomerChoices) window.utCustomerChoices.removeActiveItems();
            sel.value = '';
            return;
        }
        id = String(id);
        var exists = Array.prototype.some.call(sel.options, function (o) { return o.value === id; });
        if (!exists && window.utCustomerChoices) {
            window.utCustomerChoices.setChoices([{ value: id, label: name || ('Customer #' + id) }], 'value', 'label', false);
        }
        if (window.utCustomerChoices) window.utCustomerChoices.setChoiceByValue(id);
        sel.value = id;
    }

    // Global order search / prefill selected an order. Orders always carry a
    // customer, so this fills the Customer field and the dependent Customer
    // Order dropdown, synchronizing both. Selecting the customer is done with
    // the sync guard so it doesn't trigger a redundant reload.
    function utSetOrder(order) {
        var prevCustomer = document.getElementById('ut_customer').value;
        _utOrder = order;

        document.getElementById('ut_order_search').value = '';
        utHideOrderResults();

        var changed = prevCustomer && String(prevCustomer) !== String(order.customer_id);

        _utSyncingCustomer = true;
        utSelectCustomer(order.customer_id, order.customer_name);
        _utSyncingCustomer = false;

        utOrderMsg(changed ? 'Customer updated to match Order #' + order.order_number + '.' : '', 'warn');

        utLoadCustomerOrders(order.customer_id, order.id, {
            id:            order.id,
            order_number:  order.order_number,
            first_product: order.first_product || order.product_name || 'Order',
            extra_count:   order.extra_count || 0,
            order_date:    order.order_date || null,
            status:        order.status || null,
        });
    }

    // Clearing the order keeps the customer — a customer-level task is valid.
    function utClearOrder() {
        _utOrder = null;
        document.getElementById('ut_order_search').value = '';
        utHideOrderResults();
        utOrderMsg('');

        if (document.getElementById('ut_customer').value) {
            // Keep the loaded list; just drop back to the "Select an order" placeholder.
            if (window.utCustomerOrderChoices) window.utCustomerOrderChoices.setChoiceByValue('');
        } else {
            utResetCustomerOrder();
        }
    }

    function utRenderOrderResults(results, q) {
        var box = document.getElementById('ut_order_results');
        box.innerHTML = '';
        if (!results.length) {
            utHideOrderResults();
            utOrderMsg('No order was found for Order #' + q.replace(/^#/, '') + '.', 'error');
            return;
        }
        utOrderMsg('');
        results.forEach(function (o) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50';
            var title = document.createElement('span');
            title.className = 'font-medium text-gray-800';
            title.textContent = 'Order ' + o.order_number + ' — ' + (o.customer_name || 'no linked customer');
            row.appendChild(title);
            var meta = [o.order_date, o.status].filter(Boolean).join(' · ');
            if (meta) {
                var sub = document.createElement('span');
                sub.className = 'block text-xs text-gray-400';
                sub.textContent = meta;
                row.appendChild(sub);
            }
            row.addEventListener('click', function () { utSetOrder(o); });
            box.appendChild(row);
        });
        box.classList.remove('hidden');
    }

    document.getElementById('ut_order_search').addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(_utOrderTimer);
        if (q.length < 2) { utHideOrderResults(); utOrderMsg(''); return; }
        _utOrderTimer = setTimeout(function () {
            var seq = ++_utOrderSeq;
            fetch(UT_ORDER_LOOKUP_URL + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (seq !== _utOrderSeq) return; // stale — a newer search superseded this one
                    utRenderOrderResults(data.results || [], q);
                })
                .catch(function () {
                    if (seq === _utOrderSeq) utOrderMsg('Order lookup failed. Please try again.', 'error');
                });
        }, 300);
    });

    // Close the results dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#ut_order_search') && !e.target.closest('#ut_order_results')) {
            utHideOrderResults();
        }
    });

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
        document.getElementById('ut_equipment_block').classList.toggle('hidden', !isTask);
        document.getElementById('ut_media_block').classList.toggle('hidden', !isTask);

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

        // An order link only exists in customer mode
        if (val !== 'customer' && _utOrder) utClearOrder();

        utSetReasonChoices(val === 'supplier' ? 'supplier' : 'customer');
        if (window.utReasonChoices) window.utReasonChoices.removeActiveItems();

        utApplyOtherPhoneVisibility();
    });

    // ── Modal open/close (same global names the Task Center already uses) ──
    // Optional context ({orderId, orderNumber, customerId, customerName})
    // prefills the order/customer relationship — used by Order Details.
    // Only the relationship is prefilled; every other field stays untouched.
    window.openNewTaskModal = function (context) {
        document.getElementById('ut_title').value      = '';
        document.getElementById('ut_category').value   = '';
        document.getElementById('ut_priority').value   = 'normal';
        document.getElementById('ut_notes').value      = '';
        document.getElementById('ut_media').value      = '';
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

        utClearOrder();
        if (context && context.orderId) {
            utSetOrder({
                id:            context.orderId,
                order_number:  context.orderNumber,
                customer_id:   context.customerId || null,
                customer_name: context.customerName || null,
            });
        }

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

    // Declarative trigger: any [data-open-task-modal] element opens the modal
    // with its data-* order/customer context (inline JSON in onclick breaks on
    // quoted values like order numbers and names). Delegated, so it also works
    // for buttons injected after page load.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-open-task-modal]');
        if (!btn) return;
        openNewTaskModal({
            orderId:      parseInt(btn.dataset.orderId, 10) || null,
            orderNumber:  btn.dataset.orderNumber || null,
            customerId:   parseInt(btn.dataset.customerId, 10) || null,
            customerName: btn.dataset.customerName || null,
        });
    });

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
            orderId:    type === 'customer' && _utOrder ? _utOrder.id : null,
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

        // Multipart so attachments can ride along; append only set values
        // (FormData has no null — absent keys hit the same nullable rules)
        var fd = new FormData();
        fd.append('category', category);
        fd.append('title', title);
        fd.append('priority', document.getElementById('ut_priority').value);
        // Status is not sent — new tasks always start Open (enforced server-side).

        var fields = {
            description:          document.getElementById('ut_notes').value.trim(),
            assigned_to_user_id:  document.getElementById('ut_assigned_to').value,
            related_equipment_id: document.getElementById('ut_equip_unit').value,
            related_customer_id:  related.customerId,
            related_order_id:     related.orderId,
            related_supplier_id:  related.supplierId,
            related_other:        related.otherName,
            due_date:             document.getElementById('ut_due_date').value,
        };
        Object.keys(fields).forEach(function (key) {
            if (fields[key]) fd.append(key, fields[key]);
        });

        Array.from(document.getElementById('ut_media').files)
            .forEach(function (file) { fd.append('media[]', file); });

        fetch("{{ route('admin.tasks.store') }}", {
            method:  'POST',
            headers: {
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: fd,
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
                order_id:      related.orderId,
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

    // Close on backdrop click — unless that click is just dismissing an open
    // date picker. The flatpickr calendar lives outside the modal DOM and only
    // closes on an outside click, so without this guard the dismissing click
    // hits the backdrop and throws away the whole form. Flatpickr closes on
    // mousedown, so capture its open state before it does.
    var _utPickerWasOpen = false;
    document.addEventListener('mousedown', function () {
        _utPickerWasOpen = !!(_utFlatpickr && _utFlatpickr.isOpen);
    }, true);

    document.getElementById('UnifiedTaskModal').addEventListener('click', function (e) {
        if (e.target !== this) return;
        if (_utPickerWasOpen) return; // that click only closed the calendar
        closeNewTaskModal();
    });

}());
</script>
@endpush
