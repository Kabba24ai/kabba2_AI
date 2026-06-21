@extends('admin.layouts.app')

@section('title', 'Sales tax Report')

@push('css')
@endpush

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Sales Tax Report</h3>

    </div>

    {{-- Filters Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-3 sm:space-y-0 mb-6">
        <div class="flex flex-wrap items-end gap-4 w-full">
            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-3 py-2 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear
                </button>
            </div>

            {{-- Month Range --}}
            <div class="w-full sm:w-48">
                <select name="month_range"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 ">
                    <option value="">Choose Month Range</option>
                    @foreach ($availableMonths as $month)
                        <option value="{{ $month['value'] }}" @selected(request('month_range') == $month['value'])>
                            {{ $month['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>


            {{-- Start Date --}}
            <div class="w-full sm:w-40">
                {!! html()->text('start_date', old('start_date', request('start_date')))->class([
                        'w-full border rounded-md datepicker px-3 py-2 text-sm focus:outline-none focus:ring-2 bg-white text-gray-700',
                        'border-gray-300' => true,
                    ])->attributes([
                        'id' => 'start_date',
                        'placeholder' => 'Start Date',
                        'autocomplete' => 'off',
                    ]) !!}
            </div>

            {{-- End Date --}}
            <div class="w-full sm:w-40">
                {!! html()->text('end_date', old('end_date', request('end_date')))->class([
                        'w-full border rounded-md datepicker px-3 py-2 text-sm focus:outline-none focus:ring-2 bg-white text-gray-700',
                        'border-gray-300' => true,
                    ])->attributes([
                        'id' => 'end_date',
                        'placeholder' => 'End Date',
                        'autocomplete' => 'off',
                    ]) !!}
            </div>

            {{-- Type Dropdown --}}
            <div class="w-full sm:w-48">
                <select name="store"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(request('store') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-38">
                <select id="payment_method" name="payment_method"
                    class=" border bg-white border-gray-300 rounded-md px-3 py-2 text-sm w-full focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payment Types</option>
                    @foreach (\App\Enums\Orders\OrderPaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>
                            {{ $method->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tax Free Only --}}
            <div class="w-full sm:w-auto flex items-center gap-2 py-2">
                <input type="checkbox" id="tax_free_only" name="tax_free_only"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded cursor-pointer">
                <label for="tax_free_only" class="text-sm text-gray-700 whitespace-nowrap cursor-pointer select-none">
                    Tax Free Only
                </label>
            </div>

        </div>
    </div>

    <div class="rounded-xl dark:border-gray-800">

        <div class="dark:border-gray-800">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4  mx-auto mt-6">
                <!-- Total Revenue - All Sources  -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                            <line x1="12" x2="12" y1="2" y2="22"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Revenue - All Sources </p>
                        <p class="text-xl font-semibold text-gray-900" id="totalCollectedAllSources">

                        </p>
                    </div>
                </div>

                <!-- Total Revenue Excluding Sales Tax -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-indigo-100 text-indigo-600 rounded-md p-2">
                        <!-- Wallet Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-wallet w-6 h-6" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                            <path d="M16 12h4v4h-4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">
                            Total Revenue - Excluding Tax Free Revenue
                        </p>
                        <p class="text-xl font-semibold text-gray-900" id="totalRevenue">
                        </p>

                    </div>
                </div>


                <!-- Available Credit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Shield Icon for Tax Free Revenue (protection/exemption) -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-shield w-6 h-6" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Tax Free Revenue</p>
                        <p class="text-xl font-semibold text-gray-900" id="taxFreeRevenue">
                        </p>
                    </div>
                </div>

                <!-- Open Invoices -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                        <!-- Receipt Icon for Taxable Revenue -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-receipt w-6 h-6" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 21v-17h16v17l-4-4-4 4-4-4-4 4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Taxable Revenue</p>
                        <p class="text-xl font-semibold text-gray-900" id="taxableRevenue"></p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Percent Icon for Sales Tax -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-percent w-6 h-6" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="5" x2="5" y2="19" />
                            <circle cx="6.5" cy="6.5" r="2.5" />
                            <circle cx="17.5" cy="17.5" r="2.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Sales Tax Collected</p>
                        <p class="text-xl font-semibold text-gray-900" id="salesTaxCollected"> </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="order-table-wrapper" aria-live="polite">
        @include('admin.reports.sales_tax.partials._table', ['orders' => []])
    </div>

@endsection
@push('js')
    <script>
        window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clearFiltersBtn = document.querySelector('#clearFiltersBtn');
            let wrapper = document.querySelector('#order-table-wrapper');
            let paymentMethodInput = document.querySelector('select[name="payment_method"]');

            const monthRangeInput = document.querySelector('select[name="month_range"]');
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            const storeInput = document.querySelector('select[name="store"]');
            const taxFreeOnlyInput = document.getElementById('tax_free_only');
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            const reloadIcon = document.getElementById('reloadIcon');

            const screenKey = 'sales_tax_report_filters';
            const taxFreeKey = screenKey + '_tax_free_only';

            const fieldMap = {
                'payment_method': paymentMethodInput,
                'month_range': monthRangeInput,
                'start_date': startDateInput,
                'end_date': endDateInput,
                'store': storeInput,
            };

            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);
            if (taxFreeOnlyInput) {
                taxFreeOnlyInput.checked = localStorage.getItem(taxFreeKey) === '1';
            }

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                if (taxFreeOnlyInput) {
                    taxFreeOnlyInput.checked = false;
                    localStorage.removeItem(taxFreeKey);
                }
                fetchOrders(); // Fetch orders after clearing filters
            });

            fetchOrders(pageParam, perPageParam); // Initial fetch on page load

            function fetchOrders(page = 1, perPage = 30) {
                const params = new URLSearchParams();

                const paymentMethod = paymentMethodInput?.value || '';
                const monthRange = monthRangeInput?.value || '';
                const startDate = startDateInput?.value || '';
                const endDate = endDateInput?.value || '';
                const store = storeInput?.value || '';
                const taxFreeOnly = taxFreeOnlyInput?.checked || false;

                if (paymentMethod) params.append('payment_method', paymentMethod);
                if (monthRange) params.append('month_range', monthRange);
                if (startDate) params.append('start_date', startDate);
                if (endDate) params.append('end_date', endDate);
                if (store) params.append('store', store);
                if (taxFreeOnly) params.append('tax_free_only', '1');
                if (perPage) params.append('per_page', perPage);
                params.set('page', page);

                // save current filters
                FilterFreezer.saveFilters(screenKey, fieldMap);
                if (taxFreeOnlyInput) {
                    localStorage.setItem(taxFreeKey, taxFreeOnlyInput.checked ? '1' : '0');
                }

                wrapper.classList.add('opacity-50', 'pointer-events-none');

                //  Debug log outgoing request
                console.log('[SalesTax AJAX] Fetching with params:', params.toString());

                fetch("{{ route('admin.reports.sales-tax.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async response => {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');

                        if (!response.ok) {
                            console.error('[SalesTax AJAX] HTTP error:', response.status, response
                                .statusText);
                            let text = await response.text();
                            console.error('[SalesTax AJAX] Response text:', text);
                            alert('An error occurred while loading orders. Check console for details.');
                            return;
                        }

                        // Try parsing JSON
                        try {
                            const data = await response.json();
                            if (data?.success) {
                                wrapper.innerHTML = data.html;


                                // Update stats dynamically
                                if (data.stats) {
                                    document.querySelector('#totalRevenue').textContent = data.stats
                                        .totalRevenue;
                                    document.querySelector('#totalCollectedAllSources').textContent = data
                                        .stats.totalCollectedAllSources;
                                    document.querySelector('#taxFreeRevenue').textContent = data.stats
                                        .taxFreeRevenue;

                                        document.querySelector('#taxableRevenue').textContent = data.stats
                                        .taxableRevenue;

                                    // document.querySelector('#taxableRevenue').textContent = data.stats
                                    //     .totalRevenue;

                                    document.querySelector('#salesTaxCollected').textContent = data.stats
                                        .salesTaxCollected;
                                }

                            } else {
                                console.warn('[SalesTax AJAX] Unexpected JSON format:', data);
                                // wrapper.innerHTML = '<div class="p-4 text-red-500"> Unexpected response format</div>';
                            }
                        } catch (err) {
                            console.error('[SalesTax AJAX] JSON parse error:', err);
                            let text = await response.text();
                            console.error('[SalesTax AJAX] Raw response:', text);
                            // wrapper.innerHTML = '<div class="p-4 text-red-500">⚠️ Failed to load orders (invalid JSON)</div>';
                        }
                    })
                    .catch(error => {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        console.error('[SalesTax AJAX] Network or JS error:', error);
                        // wrapper.innerHTML = '<div class="p-4 text-red-500"> Network error — check console</div>';
                    }).finally(() => {
                        // Stop spinning
                        reloadIcon.classList.remove('animate-spin');
                    });
            }

            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchOrders
            });

            // Event bindings
            // monthRangeInput?.addEventListener('change', fetchOrders);
            // storeInput?.addEventListener('change', fetchOrders);
            // startDateInput?.addEventListener('change', fetchOrders);
            // endDateInput?.addEventListener('change', fetchOrders);
            // paymentMethodInput?.addEventListener('change', fetchOrders);

            monthRangeInput?.addEventListener('change', () => fetchOrders(1, perPageParam));
            storeInput?.addEventListener('change', () => fetchOrders(1, perPageParam));
            startDateInput?.addEventListener('change', () => fetchOrders(1, perPageParam));
            endDateInput?.addEventListener('change', () => fetchOrders(1, perPageParam));
            paymentMethodInput?.addEventListener('change', () => fetchOrders(1, perPageParam));
            taxFreeOnlyInput?.addEventListener('change', () => fetchOrders(1, perPageParam));



        });
    </script>
@endpush
