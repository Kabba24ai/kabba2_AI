@extends('admin.layouts.app')

@section('title', 'Equipment Summary Worksheet')

@section('content')
    <div class="bg-gray-50">
        <div class="p-6">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                            class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Equipment
                        </a>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Equipment Summary Worksheet</h1>
                    <p class="text-sm text-gray-600">Review and update core equipment parameters in a spreadsheet view.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @include('admin.maintenance_management.equipment_worksheet.partials._filters')

            <div id="equipment-worksheet-table-wrapper" aria-live="polite">
                @include('admin.maintenance_management.equipment_worksheet.partials._table', [
                    'equipment' => $equipment,
                    'categories' => $categories,
                    'stores' => $stores,
                    'checklistMasters' => $checklistMasters,
                    'partsLists' => $partsLists,
                ])
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const screenKey = 'equipment_worksheet_filters';

            const searchInput = document.querySelector('input[name="search"]');
            const categorySelect = document.querySelector('select[name="category"]');
            const storeSelect = document.querySelector('select[name="store"]');
            const statusSelect = document.querySelector('select[name="status"]');
            const directAssignmentSelect = document.querySelector('select[name="direct_assignment"]');
            const wrapper = document.querySelector('#equipment-worksheet-table-wrapper');
            const updateUrl = "{{ route('admin.maintenance-management.equipment-worksheet.update') }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                .search).get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            const fieldMap = {
                search: searchInput,
                category: categorySelect,
                store: storeSelect,
                status: statusSelect,
                direct_assignment: directAssignmentSelect,
            };

            FilterFreezer.loadFilters(screenKey, fieldMap);

            let timeout = null;

            function fetchWorksheet(page = 1, perPage = 40) {
                const search = searchInput.value;
                const category = categorySelect.value;
                const store = storeSelect.value;
                const status = statusSelect.value;

                const params = new URLSearchParams();
                const directAssignment = directAssignmentSelect?.value || '';

                if (search.length >= 3 || search.length === 0) params.append('search', search);
                if (category) params.append('category', category);
                if (store) params.append('store', store);
                if (status) params.append('status', status);
                if (directAssignment) params.append('direct_assignment', directAssignment);
                if (perPage) params.append('per_page', perPage);
                params.append('page', page);

                FilterFreezer.saveFilters(screenKey, fieldMap);

                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.maintenance-management.equipment-worksheet.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                        initWorksheetDatepickers(wrapper);
                        initPowerSourceToggles(wrapper);
                        requestAnimationFrame(fitWorksheetTable);
                    })
                    .finally(() => {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            function getRowsPayload() {
                const rows = {};
                const fields = wrapper.querySelectorAll('input[name^="rows["], select[name^="rows["]');

                fields.forEach(field => {
                    if (field.disabled) return;

                    const matches = field.name.match(/^rows\[([^\]]+)\]\[([^\]]+)\]$/);
                    if (!matches) return;

                    const uniqueId = matches[1];
                    const attribute = matches[2];

                    if (!rows[uniqueId]) rows[uniqueId] = {};

                    if (field.type === 'checkbox') {
                        if (field.checked) {
                            rows[uniqueId][attribute] = field.value;
                        }
                        return;
                    }

                    rows[uniqueId][attribute] = field.value;
                });

                return rows;
            }

            function setSaveButtonState(isSaving) {
                const saveButton = wrapper.querySelector('#save-worksheet-changes');
                if (!saveButton) return;

                saveButton.disabled = isSaving;
                saveButton.classList.toggle('opacity-60', isSaving);
                saveButton.classList.toggle('cursor-not-allowed', isSaving);
            }

            function saveWorksheetChanges() {
                if (!csrfToken) {
                    notyf.error('CSRF token is missing. Please refresh and try again.');
                    return;
                }

                const rows = getRowsPayload();

                setSaveButtonState(true);

                apiFetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            _method: 'PUT',
                            rows,
                        })
                    })
                    .then(response => {
                        if (response.message) {
                            notyf.success(response.message);
                        }

                        const selectedPerPage = document.getElementById('per_page_sm')?.value || perPageParam;
                        const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
                        fetchWorksheet(currentPage, selectedPerPage);
                    })
                    .finally(() => {
                        setSaveButtonState(false);
                    });
            }

            document.getElementById('clear-filters')?.addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                const selectedPerPage = document.getElementById('per_page_sm')?.value || perPageParam;
                fetchWorksheet(1, selectedPerPage);
            });

            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchWorksheet
            });

            wrapper.addEventListener('click', function(event) {
                if (event.target.id !== 'save-worksheet-changes') return;
                saveWorksheetChanges();
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const selectedPerPage = document.getElementById('per_page_sm')?.value || perPageParam;
                    fetchWorksheet(1, selectedPerPage);
                }, 400);
            });

            categorySelect.addEventListener('change', () => fetchWorksheet(1, document.getElementById('per_page_sm')
                ?.value || perPageParam));
            storeSelect.addEventListener('change', () => fetchWorksheet(1, document.getElementById('per_page_sm')
                ?.value || perPageParam));
            statusSelect.addEventListener('change', () => fetchWorksheet(1, document.getElementById('per_page_sm')
                ?.value || perPageParam));
            directAssignmentSelect?.addEventListener('change', () => fetchWorksheet(1, document.getElementById('per_page_sm')
                ?.value || perPageParam));

            fetchWorksheet(pageParam, perPageParam);

            function initWorksheetDatepickers(container) {
                container.querySelectorAll('.datepicker').forEach(el => {
                    if (el._airDatepicker) {
                        el._airDatepicker.destroy();
                    }
                    el._airDatepicker = new AirDatepicker(el, {
                        locale: window.airDatepickerLocaleEn,
                        timepicker: false,
                        dateFormat: el.dataset.format || window.APP_DATE_FORMAT || 'MM/dd/yyyy',
                        autoClose: true,
                        keyboardNav: true,
                    });
                });
            }

            function updateDefGallonsState(container, uid) {
                const hasDefCheckbox = container.querySelector(`.has-def-checkbox[data-uid="${uid}"]`);
                const defGallonsInput = container.querySelector(
                    `.def-gallons-input[name="rows[${uid}][def_tank_capacity]"]`);
                if (!defGallonsInput) return;

                const allow = !!hasDefCheckbox && !hasDefCheckbox.disabled && hasDefCheckbox.checked;
                defGallonsInput.disabled = !allow;
                defGallonsInput.classList.toggle('bg-gray-100', !allow);
                if (!allow) defGallonsInput.value = '';
            }

            function initPowerSourceToggles(container) {
                container.querySelectorAll('.power-source-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const uid = this.dataset.uid;

                        const gallonsInput = container.querySelector(
                            `.gallons-input[name^="rows[${uid}]["]`);
                        if (gallonsInput) {
                            const field = this.value === 'gas' ? 'gas_tank_capacity' : 'diesel_tank_capacity';
                            gallonsInput.name = `rows[${uid}][${field}]`;

                            const disableGallons = this.value === 'batteries' || this.value === 'electric';
                            gallonsInput.disabled = disableGallons;
                            gallonsInput.classList.toggle('bg-gray-100', disableGallons);
                            if (disableGallons) gallonsInput.value = '';
                        }

                        const isDiesel = this.value === 'diesel';
                        const hasDefCheckbox = container.querySelector(
                            `.has-def-checkbox[data-uid="${uid}"]`);
                        const hasDefHidden = hasDefCheckbox?.previousElementSibling;

                        if (hasDefCheckbox) {
                            hasDefCheckbox.disabled = !isDiesel;
                            if (hasDefHidden) hasDefHidden.disabled = !isDiesel;
                            if (!isDiesel) hasDefCheckbox.checked = false;
                        }

                        updateDefGallonsState(container, uid);
                    });
                });

                container.querySelectorAll('.has-def-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateDefGallonsState(container, this.dataset.uid);
                    });
                });
            }

            function fitWorksheetTable() {
                const el = document.getElementById('worksheet-table-scroll');
                if (!el) return;
                el.style.height = 'auto';
                const rect = el.getBoundingClientRect();
                const height = Math.max(200, window.innerHeight - rect.top - 24);
                el.style.height = height + 'px';
            }

            window.addEventListener('resize', fitWorksheetTable);
        });
    </script>
@endpush
