{{-- KPI cards for Pure Sales Summary. Included on initial load and replaced via AJAX. --}}

<div class="mb-2 text-sm text-gray-500 dark:text-gray-400">
    Period: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $dateRangeLabel }}</span>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">

    {{-- Gross Sales --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Gross Sales</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['gross_sales'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Before discounts</p>
    </div>

    {{-- Discounts --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Discounts</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">- {{ $kpis['discounts'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Applied to orders</p>
    </div>

    {{-- Net Sales --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-blue-200 dark:border-blue-700 p-4 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900">
        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Net Sales</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $kpis['net_sales'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Gross minus discounts</p>
    </div>

    {{-- Tax Collected --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tax Collected</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['tax_collected'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Not included in revenue</p>
    </div>

    {{-- Transaction Count --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Transactions</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['transaction_count'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Avg ticket: {{ $kpis['average_ticket'] }}</p>
    </div>

</div>

{{-- Revenue Breakdown Row --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Delivery Revenue</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['delivery_revenue'] }}</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Damage Waiver</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['damage_waiver_revenue'] }}</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Track Insurance</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['track_insurance_revenue'] }}</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Shipping</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['shipping_revenue'] }}</p>
    </div>

</div>
