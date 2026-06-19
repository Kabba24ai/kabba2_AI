{{-- KPI cards for Pure Sales Summary. Included on initial load and replaced via AJAX. --}}

<div class="mb-2 text-sm text-gray-500 dark:text-gray-400">
    Period: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $dateRangeLabel }}</span>
</div>

{{-- ── Row 1: Pure Sales Waterfall ─────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Gross Sales</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['gross_sales'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Before discounts &amp; refunds</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Discounts</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">- {{ $kpis['discounts'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Applied to orders</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Refunds</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">- {{ $kpis['refunds'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Against period orders</p>
    </div>

    {{-- Net Sales: the key pure-revenue metric --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-blue-200 dark:border-blue-700 p-4 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900">
        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Net Sales</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $kpis['net_sales'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Use for all analytics &amp; comparisons</p>
    </div>

    {{-- Tax Collected: separate — remitted to state, never counted as revenue --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-100 dark:border-amber-900/40 p-4 shadow-sm">
        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1">Tax Collected</p>
        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $kpis['tax_collected'] }}</p>
        <p class="text-xs text-amber-400 mt-1">Not included in revenue</p>
    </div>

</div>

{{-- ── Row 2: Ancillary Revenue Streams ───────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">

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
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tire Insurance</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['tire_insurance_revenue'] }}</p>
    </div>

    @if (($kpis['raw']['shipping_revenue'] ?? 0) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Shipping Revenue</p>
        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $kpis['shipping_revenue'] }}</p>
    </div>
    @endif

    @if (in_array($kpis['payment_status'] ?? 'paid', ['paid', 'all', 'account']))
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-200 dark:border-emerald-700 p-4 shadow-sm ring-1 ring-emerald-100 dark:ring-emerald-900">
        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">Account Payments Received</p>
        <p class="text-xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $kpis['account_payments_received'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Realized from account orders</p>
    </div>
    @endif

</div>

