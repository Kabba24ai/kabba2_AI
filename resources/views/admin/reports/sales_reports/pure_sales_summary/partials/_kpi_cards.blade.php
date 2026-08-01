{{-- KPI cards for Pure Sales Summary. Included on initial load and replaced via AJAX. --}}

<div class="mb-2 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
    <span>Period: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $dateRangeLabel }}</span></span>
    {{-- Accounting basis of this view. POD is the one order-date UNCOLLECTED
         projection — it never enters cash collections, tax collected,
         payment-date trends, or the dashboard KPIs. --}}
    @if (!empty($kpis['basis_label']))
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
            {{ ($kpis['basis'] ?? '') === 'order_date_expected'
                ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
            {{ $kpis['basis_label'] }}
        </span>
    @endif
</div>

{{-- ── Row 1: Total Collected | Gross Sales | Net Sales | Tax Collected | Refunds | Discounts ── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">

    {{-- Net Collections (fka Total Collected): supporting metric — includes tax which is remitted to government.
         gross_collections − refunds; overpayments are excluded and shown separately when present. --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-blue-200 dark:border-blue-700 p-4 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900">
        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Net Collections</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $kpis['net_collections'] ?? $kpis['total_collected'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Gross Collections &minus; Refunds</p>
        @if (($kpis['raw']['overpayments'] ?? 0) > 0)
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Overpayments {{ $kpis['overpayments'] }} excluded</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Gross Sales</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['gross_sales'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Before discounts &amp; refunds</p>
    </div>

    {{-- Net Sales: PRIMARY performance metric — actual business result --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-teal-200 dark:border-teal-700 p-4 shadow-sm ring-2 ring-teal-200 dark:ring-teal-700">
        <p class="text-xs font-semibold text-teal-600 dark:text-teal-400 uppercase tracking-wide mb-1">Net Sales ★</p>
        <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">{{ $kpis['net_sales'] }}</p>
        <p class="text-xs text-teal-500 dark:text-teal-500 mt-1">Primary performance metric</p>
    </div>

    {{-- Tax Collected: separate — remitted to state, never counted as revenue --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-100 dark:border-amber-900/40 p-4 shadow-sm">
        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1">Tax Collected</p>
        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $kpis['tax_collected'] }}</p>
        <p class="text-xs text-amber-400 mt-1">Not included in revenue</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Refunds</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">- {{ $kpis['refunds'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Against period orders</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Discounts</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">- {{ $kpis['discounts'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Applied to orders</p>
    </div>

</div>

{{-- ── Row 2: Total Account Payments | Account Payments Received | Delivery | Damage Waiver | Track Ins. | Tire Ins. ── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">

    @if (in_array($kpis['payment_status'] ?? 'paid', ['paid', 'all', 'account']))
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-300 dark:border-emerald-600 p-4 shadow-sm ring-1 ring-emerald-200 dark:ring-emerald-800">
        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300 uppercase tracking-wide mb-1">Total Account Payments</p>
        <p class="text-xl font-semibold text-emerald-800 dark:text-emerald-200">{{ $kpis['total_account_payments'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Base + sales tax collected</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-200 dark:border-emerald-700 p-4 shadow-sm ring-1 ring-emerald-100 dark:ring-emerald-900">
        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">Account Payments Received</p>
        <p class="text-xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $kpis['account_payments_received'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Base amount, excl. tax</p>
    </div>
    @else
    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-4"></div>
    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-4"></div>
    @endif

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

</div>
