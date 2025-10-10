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

        {{-- Month Range --}}
        <div class="w-full sm:w-48">
            <select name="month_range"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                <option value="">Choose Month Range</option>
                @foreach ($availableMonths as $month)
                <option value="{{ $month['value'] }}" @selected(request('month_range')==$month['value'])>
                    {{ $month['label'] }}
                </option>
                @endforeach
            </select>
        </div>


        {{-- Start Date --}}
        <div class="w-full sm:w-40">
            {!! html()->text('start_date', old('start_date', request('start_date')))
            ->class([
            'w-full border rounded-md datepicker px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 bg-white text-gray-700',
            'border-gray-300' => true,
            ])
            ->attributes([
            'id' => 'start_date',
            'placeholder' => 'Start Date',
            'autocomplete' => 'off',
            ]) !!}
        </div>

        {{-- End Date --}}
        <div class="w-full sm:w-40">
            {!! html()->text('end_date', old('end_date', request('end_date')))
            ->class([
            'w-full border rounded-md datepicker px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 bg-white text-gray-700',
            'border-gray-300' => true,
            ])
            ->attributes([
            'id' => 'end_date',
            'placeholder' => 'End Date',
            'autocomplete' => 'off',
            ]) !!}
        </div>

        {{-- Type Dropdown --}}
        <div class="w-full sm:w-48">
            <select name="store"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                <option value="">All Stores</option>
                @foreach ($stores as $store)
                <option value="{{ $store->id }}" @selected(request('store')==$store->id)>{{ $store->store_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-38">
            <select id="payment_method" name="payment_method"
                class="h-11 border bg-white border-gray-300 rounded-md px-3 py-2 text-sm w-full focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Payment Types</option>
                @foreach(\App\Enums\Orders\OrderPaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(request('payment_method')===$method->value)>
                    {{ $method->value }}
                </option>
                @endforeach
            </select>
        </div>


        {{-- Clear Filters / Reload Button --}}
        <div class="w-full sm:w-auto">
            <button id="clearFiltersBtn"
                class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm shadow-sm transition">

                <x-heroicon-o-arrow-path id="reloadIcon" class="w-5 h-5 text-white" />

                Clear Filters
            </button>
        </div>


    </div>
</div>

<div class="rounded-xl dark:border-gray-800">

    <div class="dark:border-gray-800">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6">
            <!-- Total Revenue -->
            <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                        <line x1="12" x2="12" y1="2" y2="22"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Revenue</p>
                    <p class="text-xl font-semibold text-gray-900" id="totalRevenue">
                        {{ $totalRevenue }}
                    </p>
                </div>
            </div>

            <!-- Available Credit -->
            <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                <div class="bg-green-100 text-green-600 rounded-md p-2">
                    <!-- Shield Icon for Tax Free Revenue (protection/exemption) -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-shield w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm  text-gray-500">Tax Free Revenue</p>
                    <p class="text-xl font-semibold text-gray-900" id="taxFreeRevenue">

                        {{ $taxFreeRevenue }}
                    </p>
                </div>
            </div>

            <!-- Open Invoices -->
            <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                    <!-- Receipt Icon for Taxable Revenue -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-receipt w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 21v-17h16v17l-4-4-4 4-4-4-4 4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm  text-gray-500">Taxable Revenue</p>
                    <p class="text-xl font-semibold text-gray-900" id="taxableRevenue">{{ $taxableRevenue }}</p>
                </div>
            </div>

            <!-- Last Payment -->
            <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                <div class="bg-green-100 text-green-600 rounded-md p-2">
                    <!-- Percent Icon for Sales Tax -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-percent w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="5" x2="5" y2="19" />
                        <circle cx="6.5" cy="6.5" r="2.5" />
                        <circle cx="17.5" cy="17.5" r="2.5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm  text-gray-500">Sales Tax Collected</p>
                    <p class="text-xl font-semibold text-gray-900" id="salesTaxCollected">{{ $salesTaxCollected }} </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="order-table-wrapper" aria-live="polite">
    @include('admin.reports.sales_tax.partials._table', ['orders' => $orders])
</div>

@endsection
@push('js')
<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>

@endpush