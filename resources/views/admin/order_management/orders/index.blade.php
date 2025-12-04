@extends('admin.layouts.app')

@section('title', 'Orders')

@push('css')
@endpush

@section('content')

@include('flash::message')

{{-- Header --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <h1 class="text-2xl font-semibold flex items-center gap-2">
        <x-heroicon-o-shopping-cart class="w-6 h-6 text-blue-600" />
        Order Management
    </h1>
    <a href="{{ route('admin.order-management.orders.index') }}"
        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium text-md flex items-center gap-2">
        <x-heroicon-o-arrow-path class="w-5 h-5" />
        Reload
    </a>
</div>

{{-- Filters Row --}}
<div class="bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-4 sm:space-y-0 mb-6">
    <div class="flex flex-wrap items-end gap-4 w-full">
        <div class="w-full sm:w-48">
            <div class="relative bg-white">
                <input type="text" id="customer_name" placeholder="Customer name" name="customer_name"
                    value="{{ request('customer_name') }}"
                    class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                <x-heroicon-o-magnifying-glass
                    class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
            </div>
        </div>
        <div class="w-full sm:w-48">
            <div class="relative bg-white">
                <input type="text" id="customer_company_name" placeholder="Customer company"
                    name="customer_company_name" value="{{ request('customer_company_name') }}"
                    class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                <x-heroicon-o-magnifying-glass
                    class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
            </div>
        </div>
        <div class="w-full sm:w-38">
            <div class="relative bg-white">
                <input type="text" id="customer_phone" placeholder="(xxx) xxx-xxxx" name="customer_phone"
                    value="{{ request('customer_phone') }}"
                    class="masked-phone pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
            </div>
        </div>
        <div class="w-full sm:w-48">
            <select name="category"
                class="choices-select w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Category</option>
                @foreach ($categories as $id => $title)
                <option value="{{ $id }}" @selected(request('category')==$id)>
                    {{ $title }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <select name="product"
                class="choices-select w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Products</option>
                @foreach ($products as $id => $title)
                <option value="{{ $id }}" @selected(request('product')==$id)>
                    {{ $title }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-38">
            <select id="payment_method" name="payment_method"
                class=" border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-full focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Payment Types</option>
                @foreach(\App\Enums\Orders\OrderPaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(request('payment_method')===$method->value)>
                    {{ $method->value }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-36">
            <select id="payment_status" name="payment_status"
                class=" border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-full focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Payments</option>
                @foreach(\App\Enums\Orders\OrderPaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('payment_status')===$status->value)>
                    {{ $status->label() }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <button type="button" id="delete-selected-btn"
                class=" flex items-center gap-2 bg-gray-300 text-gray-500 cursor-not-allowed py-3 px-3 rounded-md text-sm font-medium transition w-full sm:w-auto"
                disabled>
                <x-heroicon-o-trash class="w-4 h-4" />
                Delete Selected (<span id="delete-selected-count">0</span>)
            </button>
        </div>
    </div>
</div>


<div id="order-table-wrapper" aria-live="polite">
    @include('admin.order_management.orders.partials._table', ['orders' => []])
</div>


@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initOrderCheckboxes() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const orderCheckboxes = document.querySelectorAll('.order-checkbox');
            const deleteBtn = document.getElementById('delete-selected-btn');
            const deleteCountSpan = document.getElementById('delete-selected-count');

            function updateDeleteBtnCount() {
                const orderCheckboxes = document.querySelectorAll('.order-checkbox');
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
                    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
                    const ids = [...orderCheckboxes].filter(cb => cb.checked).map(cb => cb.value);

                    if (ids.length === 0) {
                        notyf.error('Please select at least one order to delete.', 'No orders selected!');
                        return;
                    }

                    window.showConfirm(
                        `Delete ${ids.length} order(s)? This action cannot be undone!`,
                        'Delete Orders'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            apiFetch("{{ route('admin.order-management.orders.bulk-delete') }}", {
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
                                                'order-row-' + id);
                                            if (row) row.remove();
                                        });
                                        // Reset select all and count
                                        if (selectAllCheckbox) selectAllCheckbox.checked = false;
                                        updateDeleteBtnCount();
                                        fetchOrders();
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
                        `Delete this order? This action cannot be undone!`,
                        'Delete Order'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('admin.order-management.orders.bulk-delete') }}", {
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
                                        const row = document.getElementById('order-row-' +
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

        initOrderCheckboxes();
        initSingleDeleteButtons();

        let customerNameInput = document.querySelector('input[name="customer_name"]');
        let customerCompanyNameInput = document.querySelector('input[name="customer_company_name"]');
        let customerPhoneInput = document.querySelector('input[name="customer_phone"]');
        let categoryInput = document.querySelector('select[name="category"]');
        let paymentMethodInput = document.querySelector('select[name="payment_method"]');
        let paymentStatusInput = document.querySelector('select[name="payment_status"]');
        let productInput = document.querySelector('select[name="product"]');

        let wrapper = document.querySelector('#order-table-wrapper');
        let timeout = null;
        const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search)
                .get('per_page') || null;
        const pageParam = new URLSearchParams(window.location.search).get('page') || 1;


        const screenKey = "order_filters";

        const fieldMap = {
            'customer_name': customerNameInput,
            'customer_company_name': customerCompanyNameInput,
            'customer_phone': customerPhoneInput,
            'category': categoryInput,
            'payment_method': paymentMethodInput,
            'payment_status': paymentStatusInput,
            'product': productInput,
        };

        // Load saved filters on page load
        FilterFreezer.loadFilters(screenKey, fieldMap);
        fetchOrders(pageParam, perPageParam); // initial fetch after loading saved filters
        function fetchOrders(page = 1, perPage = 10) {
            const customerName = customerNameInput.value;
            const customerCompany = customerCompanyNameInput.value;
            const customerPhone = customerPhoneInput.value;
            const category = categoryInput.value;
            const paymentMethod = paymentMethodInput.value;
            const paymentStatus = paymentStatusInput.value;
            const product = productInput.value;

            const params = new URLSearchParams();
            params.set('page', 1); // Always reset to first page on filter
            if (customerName.length >= 3 || customerName.length === 0) params.append('customer_name',
                customerName);
            if (customerCompany.length >= 3 || customerCompany.length === 0) params.append(
                'customer_company_name',
                customerCompany);
            if (customerPhone.length >= 3 || customerPhone.length === 0) params.append('customer_phone',
                customerPhone);
            if (category) params.append('category', category);
            if (paymentMethod) params.append('payment_method', paymentMethod);
            if (paymentStatus) params.append('payment_status', paymentStatus);
            if (product) params.append('product', product);
            if (perPage) params.append('per_page', perPage);
            params.set('page', page); // Set the current page

            // Save current filters
            FilterFreezer.saveFilters(screenKey, fieldMap);
            wrapper.classList.add('opacity-50', 'pointer-events-none');

            apiFetch("{{ route('admin.order-management.orders.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, {
                    loaderSelector: '#order-loading',
                    containerSelector: '#order-table-wrapper'
                })
                .then(response => {
                    wrapper.innerHTML = response.html;
                    initOrderCheckboxes(); // Reinitialize checkboxes after new content
                    initSingleDeleteButtons();
                })
        }

        // Register pagination
        Paginator.init({
            wrapper: wrapper,
            fetchCallback: fetchOrders
        });

        // Debounce search input
        customerNameInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchOrders, 400); // Wait 400ms before firing
        });

        customerCompanyNameInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchOrders, 400); // Wait 400ms before firing
        });

        customerPhoneInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchOrders, 400); // Wait 400ms before firing
        });



        // Instant change on selects
        productInput.addEventListener('change', fetchOrders);
        categoryInput.addEventListener('change', fetchOrders);
        paymentMethodInput.addEventListener('change', fetchOrders);
        paymentStatusInput.addEventListener('change', fetchOrders);
    });
</script>
@endpush
