@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3 sm:gap-4">

        {{-- Left side: Title --}}
        <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
            Customers
        </h3>

        {{-- Right side: Action buttons --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.crm.customers.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-3 text-md font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 w-full sm:w-auto text-center">
                + Add Customer
            </a>

            <a href="javascript:void(0)" onclick="openModal('TagModalWrapper')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 text-md font-medium shadow transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500 w-full sm:w-auto text-center">
                Manage Tags
            </a>
        </div>
    </div>



    <div class="flex flex-wrap items-end gap-4 w-full mb-6 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="w-full sm:w-auto">
            <button type="button" id="clear-filters"
                class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Clear
            </button>
        </div>

        <!-- Search by name -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_name" placeholder="Customer name" value="{{ request('search_name') }}"
                class="w-full px-4 py-3 rounded-md border border-gray-300 bg-white pl-3 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

        <!-- Search by company -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_company_name" placeholder="Customer company"
                value="{{ request('search_company_name') }}"
                class="w-full px-4 py-3 rounded-md border border-gray-300 bg-white pl-3 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

        <!-- Search by phone -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_phone" placeholder="(xxx) xxx-xxxx" value="{{ request('search_phone') }}"
                class="masked-phone w-full rounded-md border border-gray-300 bg-white pl-3 pr-10 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07
                                    19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1
                                    4.11 2h3a2 2 0 0 1 2 1.72 12.44 12.44 0 0 0 .7 2.81 2 2 0 0 1-.45
                                    2.11L8 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45
                                    12.44 12.44 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
            </div>
        </div>
        <div class="w-full sm:w-48">

            <select
                class="w-full  border rounded-md px-4 py-3 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300"
                name="tax_status" id="tax_status">
                <option value="All" {{ request('status') === 'All' ? 'selected' : '' }}>All</option>
                <option value="Exempt" {{ request('status') === 'Exempt' ? 'selected' : '' }}>Exempt</option>
                <option value="Taxable" {{ request('status') === 'Taxable' ? 'selected' : '' }}>Taxable</option>
            </select>


        </div>


        <!-- Total count -->

        <div
            class="w-full sm:w-auto px-4 py-3 rounded-md border border-gray-300 text-sm text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 text-center sm:text-left">
            Total: <span id="customer-total-count"></span>
        </div>

        <div class="w-full sm:w-auto">
            <button type="button" id="delete-selected-btn"
                class="flex items-center px-4 py-3 gap-2 bg-gray-300 text-gray-500 cursor-not-allowed rounded-md text-sm font-medium transition w-full sm:w-auto"
                disabled>
                <x-heroicon-o-trash class="w-4 h-4" />
                Delete Selected (<span id="delete-selected-count">0</span>)
            </button>
        </div>

    </div>




    <div id="customer-table-wrapper">

        @include('admin.crm.customers.partials._table', ['customers' => []])

    </div>

    @include('admin.crm.customers.partials._model_tag')


@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let nameInput = document.querySelector('input[name="search_name"]');
            let phoneInput = document.querySelector('input[name="search_phone"]');
            let company_name = document.querySelector('input[name="search_company_name"]');
            let statusSelect = document.querySelector('select[name="tax_status"]');
            let wrapper = document.querySelector('#customer-table-wrapper');

            let loader = document.querySelector('#customer-loader');
            let timeout = null;
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            const screenKey = "customers_filters";

            const fieldMap = {
                'search_name': nameInput,
                'search_phone': phoneInput,
                'search_company_name': company_name,
                'tax_status': statusSelect,
            };

            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchCustomers(1); // reload first page
            });

            fetchCustomers(pageParam, perPageParam); // initial fetch
            function fetchCustomers(page = 1, perPage = 30) {
                const name = nameInput.value;
                const phone = phoneInput.value;
                const company = company_name.value;
                const tax_status = statusSelect.value;

                const params = new URLSearchParams();
                if (name.length >= 3 || name.length === 0) params.append('search_name', name);
                if (phone.length >= 3 || phone.length === 0) params.append('search_phone', phone);
                if (company.length >= 3 || company.length === 0) params.append('search_company_name', company);
                if (tax_status !== 'All') params.append('tax_status', tax_status);
                if (perPage) params.append('per_page', perPage);
                params.append('page', page);

                loader.classList.remove('hidden');

                // Save current filters
                FilterFreezer.saveFilters(screenKey, fieldMap);
                wrapper.classList.add('opacity-50', 'pointer-events-none');


                fetch("{{ route('admin.crm.customers.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.querySelector('#customer-table-wrapper').innerHTML = data.html;
                        document.querySelector('#customer-total-count').textContent = data.total;

                        // FIX: reinitialize events
                        initOrderCheckboxes();
                        initSingleDeleteButtons();
                    })

                    .catch(err => {
                        wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading customers.</div>';
                        console.error(err);
                    }).finally(() => {
                        // Hide loader
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');

                    });

            }


            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchCustomers
            });

            // Delayed filters
            nameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchCustomers, 400);
            });

            phoneInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchCustomers, 400);
            });

            company_name.addEventListener('input', function() {

                clearTimeout(timeout);
                timeout = setTimeout(fetchCustomers, 400);
            });

            // Immediate filter for tax_status
            statusSelect.addEventListener('change', function() {
                fetchCustomers(); // no timeout
            });
        });
    </script>


    <script>
        // bulk delete



        function initOrderCheckboxes() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const orderCheckboxes = document.querySelectorAll('.customer-checkbox');
            const deleteBtn = document.getElementById('delete-selected-btn');
            const deleteCountSpan = document.getElementById('delete-selected-count');

            function updateDeleteBtnCount() {
                const orderCheckboxes = document.querySelectorAll('.customer-checkbox');
                const count = [...orderCheckboxes].filter(cb => cb.checked).length;
                deleteCountSpan.textContent = count;

                if (deleteBtn) {
                    if (count === 0) {
                        deleteBtn.disabled = true;
                        deleteBtn.classList.remove('bg-red-600', 'text-white', 'hover:bg-red-700', 'cursor-pointer');
                        deleteBtn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                    } else {
                        deleteBtn.disabled = false;
                        deleteBtn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                        deleteBtn.classList.add('bg-red-600', 'text-white', 'hover:bg-red-700', 'cursor-pointer');
                    }
                }
            }

            // Select all functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    orderCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                    updateDeleteBtnCount();
                });
            }
            // Update 'Select All' checkbox if any item is unchecked
            orderCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = [...orderCheckboxes].every(cb => cb.checked);
                    }
                    updateDeleteBtnCount();
                });
            });
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const orderCheckboxes = document.querySelectorAll('.customer-checkbox');
                    const ids = [...orderCheckboxes].filter(cb => cb.checked).map(cb => cb.value);

                    if (ids.length === 0) {
                        notyf.error('Please select at least one customer to delete.', 'No customer selected!');
                        return;
                    }

                    window.showConfirm(
                        `Delete ${ids.length} customer(s)? This action cannot be undone!`,
                        'Delete Customers'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            apiFetch("{{ route('admin.crm.customers.bulk-delete') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        unique_ids: ids
                                    })
                                })
                                .then(data => {

                                    if (data.success || (data.message && data.message
                                            .toLowerCase().includes('deleted'))) {
                                        notyf.success(data.message,
                                            'Deleted!');
                                        // Remove rows
                                        ids.forEach(function(id) {
                                            const row = document.getElementById(
                                                'customer-row-' + id);

                                            if (row) row.remove();
                                        });
                                        // Reset select all and count
                                        if (selectAllCheckbox) selectAllCheckbox.checked = false;
                                        updateDeleteBtnCount();
                                    } else {
                                        notyf.error(data.message);
                                    }
                                })
                        }
                    });
                });
            }

            updateDeleteBtnCount();
        }

        function initSingleDeleteButtons() {
            const singleDeleteButtons = document.querySelectorAll('.delete-button');

            singleDeleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const uniqueId = this.dataset.uniqueId;

                    window.showConfirm(
                        `Delete this Customer? This action cannot be undone!`,
                        'Delete Customer'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('admin.crm.customers.bulk-delete') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        unique_ids: [uniqueId] // send as an array
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {

                                    if (data.success || (data.message && data.message
                                            .toLowerCase().includes('deleted'))) {
                                        notyf.success(data.message, 'Deleted!');
                                        const row = document.getElementById('customer-row-' +
                                            uniqueId);

                                        if (row) row.remove();
                                    } else {
                                        notyf.error(data.message);
                                    }
                                })
                        }
                    });
                });
            });
        }




        document.addEventListener('DOMContentLoaded', function() {
            initOrderCheckboxes();
            initSingleDeleteButtons();
        });


        // bulk delete
    </script>



    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // === Common Modal Functions ===
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.remove('hidden');
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.add('hidden');
            }


            // === Optional: Close when clicking outside modal content ===
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-wrapper')) {
                    e.target.classList.add('hidden');
                }
            });

            // === Make globally accessible ===
            window.openModal = openModal;
            window.closeModal = closeModal;
        });
    </script>
@endpush
