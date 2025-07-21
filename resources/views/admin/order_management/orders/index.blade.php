@extends('admin.layouts.app')

@section('title', 'Orders')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Orders Management</h3>
        <a href="#"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + New Order
        </a>
    </div>

    {{-- Filters Row --}}
    <div class="bg-white p-4 rounded-md shadow-sm mb-6">
        @include('admin.partials.formErrors')
        <div class="flex flex-wrap items-end gap-4">

            {{-- Search Name --}}
            <div>
                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Search Name</label>
                <div class="relative">
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" />
                    <input type="text" id="customer_name" placeholder="Customer name..." name="customer_name"
                        value="{{ request('customer_name') }}"
                        class="pl-10 pr-4 py-2 h-11 border border-gray-300 rounded-md text-sm w-48 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            {{-- Search Phone --}}
            <div>
                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">Search Phone</label>
                <div class="relative">
                    <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" />
                    <input type="text" id="customer_phone" placeholder="(xxx) xxx-xxxx" name="customer_phone"
                        value="{{ request('customer_phone') }}"
                        class="masked-phone pl-10 pr-4 py-2 h-11 border border-gray-300 rounded-md text-sm w-48 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            {{-- Category --}}
            <div class="w-full sm:w-48">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category"
                    class="choices-select w-full  rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                            {{ $category->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Method --}}
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="payment_method" name="payment_method"
                    class="h-11 border border-gray-300 rounded-md px-3 py-2 text-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Methods</option>
                    <option value="Card" @selected(request('payment_method') == 'Card')>Card</option>
                    <option value="COD" @selected(request('payment_method') == 'COD')>Cash</option>
                    <option value="Account" @selected(request('payment_method') == 'Account')>Account</option>
                </select>
            </div>

            {{-- Payment Status --}}
            <div>
                <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select id="payment_status" name="payment_status"
                    class="h-11 border border-gray-300 rounded-md px-3 py-2 text-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="Completed" @selected(request('payment_status') == 'Completed')>Paid</option>
                    <option value="Pending" @selected(request('payment_status') == 'Pending')>Pending</option>
                    <option value="Failed" @selected(request('payment_status') == 'Failed')>Failed</option>
                </select>
            </div>

            {{-- Delete Button --}}
            <div class="ml-auto">
                <button type="button" id="delete-selected-btn"
                    class="h-11 flex items-center gap-2 bg-gray-300 text-gray-500 cursor-not-allowed px-4 py-2 rounded-md text-sm font-medium transition"
                    disabled>
                    <x-heroicon-o-trash class="w-4 h-4" />
                    Delete Selected (<span id="delete-selected-count">0</span>)
                </button>

            </div>

        </div>
    </div>

    <div id="order-table-wrapper" aria-live="polite">
        @include('admin.order_management.orders.partials._table', ['orders' => $orders])
    </div>


@endsection

@push('js')
    <script>

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
                            fetch("{{ route('admin.order-management.orders.bulk-delete') }}", {
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
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success || (data.message && data.message
                                            .toLowerCase().includes('deleted'))) {
                                        notyf.success('Selected orders have been deleted.',
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
                                    } else {
                                        notyf.error(data.message ||
                                            'Could not delete selected orders.', 'Failed!');
                                    }
                                }).catch(() => {
                                    notyf.error('Something went wrong. Please try again.',
                                        'Error!');
                                });
                        }
                    });
                });
            }

            updateDeleteBtnCount();
        }

        document.addEventListener('DOMContentLoaded', function() {
            initOrderCheckboxes();

            let customerNameInput = document.querySelector('input[name="customer_name"]');
            let customerPhoneInput = document.querySelector('input[name="customer_phone"]');
            let categoryInput = document.querySelector('select[name="category"]');
            let paymentMethodInput = document.querySelector('select[name="payment_method"]');
            let paymentStatusInput = document.querySelector('select[name="payment_status"]');

            let loader = document.querySelector('#order-loading');
            let wrapper = document.querySelector('#order-table-wrapper');
            let timeout = null;

            function fetchOrders() {
                const customerName = customerNameInput.value;
                const customerPhone = customerPhoneInput.value;
                const category = categoryInput.value;
                const paymentMethod = paymentMethodInput.value;
                const paymentStatus = paymentStatusInput.value;

                const params = new URLSearchParams();
                params.set('page', 1); // Always reset to first page on filter
                if (customerName.length >= 3 || customerName.length === 0) params.append('customer_name',
                    customerName);
                if (customerPhone.length >= 3 || customerPhone.length === 0) params.append('customer_phone',
                    customerPhone);
                if (category) params.append('category', category);
                if (paymentMethod) params.append('payment_method', paymentMethod);
                if (paymentStatus) params.append('payment_status', paymentStatus);

                // Show loader
                loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch("{{ route('admin.order-management.orders.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        wrapper.innerHTML = html;
                        initOrderCheckboxes();
                    })
                    .catch(error => {
                        wrapper.innerHTML =
                            '<div class="text-red-500 p-4">Something went wrong loading the data.</div>';
                        console.error('Error fetching products:', error);
                    })
                    .finally(() => {
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            // Debounce search input
            customerNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchOrders, 400); // Wait 400ms before firing
            });

            customerPhoneInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchOrders, 400); // Wait 400ms before firing
            });

            // Instant change on selects
            categoryInput.addEventListener('change', fetchOrders);
            paymentMethodInput.addEventListener('change', fetchOrders);
            paymentStatusInput.addEventListener('change', fetchOrders);
        });
    </script>
@endpush
