{{--
    Payment Reconciliation Ledger — one row per payment event.
    Included via AJAX (tab=ledger). Supports 3 view modes:
      chronological   — flat table sorted by date
      revenue_source  — grouped by revenue source with subtotals
      payment_method  — grouped by payment method with subtotals
--}}
@php
    $ledgerView  = $ledger_view ?? 'chronological';
    $grandTotal  = $rows->sum('grand_total');
    $totalBase   = $rows->sum('base_amount');
    $totalTax    = $rows->sum('tax_amount');
    $rowCount    = $rows->count();

    // Payment method summary (always rendered, regardless of view mode)
    $pmOrder  = \App\Services\Reports\PaymentReconciliationLedger::paymentMethodOrder();
    $pmGroups = $rows->groupBy('payment_method_key')
        ->sortBy(fn ($g, $k) => $pmOrder[$k] ?? 99);

    // Revenue source sort order for the grouped view
    $rsOrder = \App\Services\Reports\PaymentReconciliationLedger::revenueSourceOrder();
@endphp

@if ($rows->isEmpty())
    <div class="py-16 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm font-medium">No payments found for this period.</p>
        <p class="text-xs mt-1">Try adjusting the date range or removing filters.</p>
    </div>
@else

{{-- ── Summary strip ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center gap-4 px-1 mb-4 text-sm text-gray-600 dark:text-gray-300">
    <span>
        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($rowCount) }}</span>
        payment{{ $rowCount === 1 ? '' : 's' }}
    </span>
    <span class="text-gray-300 dark:text-gray-600">|</span>
    <span>Base: <span class="font-semibold text-gray-900 dark:text-white">{{ \App\Helpers\CustomHelper::formatCurrency($totalBase) }}</span></span>
    <span class="text-gray-300 dark:text-gray-600">|</span>
    <span>Tax: <span class="font-semibold text-gray-900 dark:text-white">{{ \App\Helpers\CustomHelper::formatCurrency($totalTax) }}</span></span>
    <span class="text-gray-300 dark:text-gray-600">|</span>
    <span>Grand Total:
        <span class="font-bold text-blue-700 dark:text-blue-300 text-base">{{ \App\Helpers\CustomHelper::formatCurrency($grandTotal) }}</span>
    </span>
</div>

{{-- ── CHRONOLOGICAL VIEW ────────────────────────────────────────────────────── --}}
@if ($ledgerView === 'chronological')
<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
<table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
<thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase tracking-wide">
<tr>
    <th class="px-3 py-2 text-left whitespace-nowrap">Payment Date</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Order #</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Orig. Order Date</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Customer</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Revenue Source</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Payment Method</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Status</th>
    <th class="px-3 py-2 text-right whitespace-nowrap">Base Amt</th>
    <th class="px-3 py-2 text-right whitespace-nowrap">Tax</th>
    <th class="px-3 py-2 text-right whitespace-nowrap font-semibold">Total</th>
    <th class="px-3 py-2 text-left">Included Because</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-100 dark:divide-gray-700">
@foreach ($rows as $row)
@php
    $isNegative = $row->grand_total < 0;
    $amtClass   = $isNegative ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white';
@endphp
<tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
    <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-600 dark:text-gray-400">
        {{ $row->payment_date ? \Carbon\Carbon::parse($row->payment_date)->format('M j, Y') : '—' }}
    </td>
    <td class="px-3 py-2 whitespace-nowrap">
        @if ($row->order_unique_id)
            <a href="{{ route('admin.order-management.orders.edit', $row->order_unique_id) }}"
               class="text-brand-500 hover:underline font-medium">{{ $row->order_number }}</a>
        @else
            <span class="text-gray-400">{{ $row->order_number }}</span>
        @endif
    </td>
    <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400 font-mono">
        {{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('M j, Y') : '—' }}
    </td>
    <td class="px-3 py-2 max-w-[140px] truncate" title="{{ $row->customer_name }}">{{ $row->customer_name }}</td>
    <td class="px-3 py-2 whitespace-nowrap">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
            {{ match($row->stream) {
                'refund'  => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                'account' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                'billing' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                default   => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            } }}">
            {{ $row->revenue_source }}
        </span>
    </td>
    <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row->payment_method }}</td>
    <td class="px-3 py-2 whitespace-nowrap">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs
            {{ str_contains(strtolower($row->payment_status ?? ''), 'refund') || str_contains(strtolower($row->payment_status ?? ''), 'voided')
                ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400'
                : 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' }}">
            {{ $row->payment_status }}
        </span>
    </td>
    <td class="px-3 py-2 text-right whitespace-nowrap {{ $amtClass }}">
        {{ \App\Helpers\CustomHelper::formatCurrency($row->base_amount) }}
    </td>
    <td class="px-3 py-2 text-right whitespace-nowrap text-amber-600 dark:text-amber-400">
        {{ $row->tax_amount != 0 ? \App\Helpers\CustomHelper::formatCurrency($row->tax_amount) : '—' }}
    </td>
    <td class="px-3 py-2 text-right whitespace-nowrap font-semibold {{ $amtClass }}">
        {{ \App\Helpers\CustomHelper::formatCurrency($row->grand_total) }}
    </td>
    <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs max-w-[220px]">
        {{ $row->included_because }}
        @if ($row->stream === 'refund')
            @include('admin.reports.sales_reports.pure_sales_summary.partials._ledger_refund_detail', ['row' => $row])
        @endif
    </td>
</tr>
@endforeach
</tbody>
<tfoot class="bg-gray-50 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600">
<tr>
    <td colspan="7" class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
        Grand Total ({{ number_format($rowCount) }} payments)
    </td>
    <td class="px-3 py-2 text-right text-xs font-semibold text-gray-900 dark:text-white">
        {{ \App\Helpers\CustomHelper::formatCurrency($totalBase) }}
    </td>
    <td class="px-3 py-2 text-right text-xs font-semibold text-amber-600 dark:text-amber-400">
        {{ \App\Helpers\CustomHelper::formatCurrency($totalTax) }}
    </td>
    <td class="px-3 py-2 text-right font-bold text-blue-700 dark:text-blue-300">
        {{ \App\Helpers\CustomHelper::formatCurrency($grandTotal) }}
    </td>
    <td></td>
</tr>
</tfoot>
</table>
</div>
@endif

{{-- ── REVENUE SOURCE VIEW ───────────────────────────────────────────────────── --}}
@if ($ledgerView === 'revenue_source')
@php
    $rsByKey = $rows->groupBy('revenue_source_key')
        ->sortBy(fn ($g, $k) => $rsOrder[$k] ?? 99);
@endphp
@foreach ($rsByKey as $sourceKey => $sourceRows)
@php
    $sourceLabel = $sourceRows->first()->revenue_source;
    $sourceBase  = $sourceRows->sum('base_amount');
    $sourceTax   = $sourceRows->sum('tax_amount');
    $sourceTotal = $sourceRows->sum('grand_total');
    $sourceCount = $sourceRows->count();
    $streamType  = $sourceRows->first()->stream;
@endphp
<div class="mb-6">
    {{-- Group header --}}
    <div class="flex items-center justify-between px-4 py-2 rounded-t-lg border border-gray-200 dark:border-gray-700
        {{ match($streamType) {
            'refund'  => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700',
            'account' => 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700',
            'billing' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700',
            default   => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700',
        } }}">
        <div class="flex items-center gap-3">
            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $sourceLabel }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $sourceCount }} payment{{ $sourceCount !== 1 ? 's' : '' }}</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span class="text-gray-500 dark:text-gray-400 text-xs">
                Base: {{ \App\Helpers\CustomHelper::formatCurrency($sourceBase) }}
                &nbsp;|&nbsp; Tax: {{ \App\Helpers\CustomHelper::formatCurrency($sourceTax) }}
            </span>
            <span class="font-bold text-gray-900 dark:text-white">{{ \App\Helpers\CustomHelper::formatCurrency($sourceTotal) }}</span>
        </div>
    </div>
    {{-- Group rows --}}
    <div class="overflow-x-auto border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-lg">
    <table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
    <thead class="bg-gray-50 dark:bg-gray-750 text-gray-400 dark:text-gray-500 text-xs">
    <tr>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Payment Date</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Order #</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Orig. Order Date</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Customer</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Payment Method</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Status</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap">Base</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap">Tax</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap font-medium">Total</th>
    </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
    @foreach ($sourceRows->sortByDesc('payment_date') as $row)
    @php $isNeg = $row->grand_total < 0; $cls = $isNeg ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'; @endphp
    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
        <td class="px-3 py-1.5 font-mono text-gray-500">
            {{ $row->payment_date ? \Carbon\Carbon::parse($row->payment_date)->format('M j, Y') : '—' }}
        </td>
        <td class="px-3 py-1.5">
            @if ($row->order_unique_id)
                <a href="{{ route('admin.order-management.orders.edit', $row->order_unique_id) }}"
                   class="text-brand-500 hover:underline font-medium">{{ $row->order_number }}</a>
            @else
                <span class="text-gray-400">{{ $row->order_number }}</span>
            @endif
        </td>
        <td class="px-3 py-1.5 font-mono text-gray-400">
            {{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('M j, Y') : '—' }}
        </td>
        <td class="px-3 py-1.5 max-w-[220px] truncate" title="{{ $row->customer_name }}">
            {{ $row->customer_name }}
            @if ($row->stream === 'refund')
                @include('admin.reports.sales_reports.pure_sales_summary.partials._ledger_refund_detail', ['row' => $row])
            @endif
        </td>
        <td class="px-3 py-1.5 text-gray-500">{{ $row->payment_method }}</td>
        <td class="px-3 py-1.5">
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs
                {{ str_contains(strtolower($row->payment_status ?? ''), 'refund') ? 'bg-red-50 text-red-600 dark:text-red-400' : 'bg-green-50 text-green-700 dark:text-green-300' }}">
                {{ $row->payment_status }}
            </span>
        </td>
        <td class="px-3 py-1.5 text-right {{ $cls }}">{{ \App\Helpers\CustomHelper::formatCurrency($row->base_amount) }}</td>
        <td class="px-3 py-1.5 text-right text-amber-500">
            {{ $row->tax_amount != 0 ? \App\Helpers\CustomHelper::formatCurrency($row->tax_amount) : '—' }}
        </td>
        <td class="px-3 py-1.5 text-right font-semibold {{ $cls }}">{{ \App\Helpers\CustomHelper::formatCurrency($row->grand_total) }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <tr>
        <td colspan="6" class="px-3 py-1.5 text-right text-xs font-semibold text-gray-500 uppercase">
            Subtotal — {{ $sourceLabel }}
        </td>
        <td class="px-3 py-1.5 text-right text-xs font-semibold text-gray-900 dark:text-white">
            {{ \App\Helpers\CustomHelper::formatCurrency($sourceBase) }}
        </td>
        <td class="px-3 py-1.5 text-right text-xs font-semibold text-amber-600">
            {{ \App\Helpers\CustomHelper::formatCurrency($sourceTax) }}
        </td>
        <td class="px-3 py-1.5 text-right font-bold text-gray-900 dark:text-white">
            {{ \App\Helpers\CustomHelper::formatCurrency($sourceTotal) }}
        </td>
    </tr>
    </tfoot>
    </table>
    </div>
</div>
@endforeach

{{-- Grand total row --}}
<div class="mt-2 flex justify-end">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-6 py-3 flex items-center gap-8">
        <span class="text-sm text-gray-600 dark:text-gray-300">{{ number_format($rowCount) }} payments</span>
        <div class="text-right">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Grand Total</div>
            <div class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ \App\Helpers\CustomHelper::formatCurrency($grandTotal) }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── PAYMENT METHOD VIEW ───────────────────────────────────────────────────── --}}
@if ($ledgerView === 'payment_method')
@php
    $pmByKey = $rows->groupBy('payment_method_key')
        ->sortBy(fn ($g, $k) => $pmOrder[$k] ?? 99);
@endphp
@foreach ($pmByKey as $pmKey => $pmRows)
@php
    $pmLabel  = $pmRows->first()->payment_method;
    $pmBase   = $pmRows->sum('base_amount');
    $pmTax    = $pmRows->sum('tax_amount');
    $pmTotal  = $pmRows->sum('grand_total');
    $pmCount  = $pmRows->count();
@endphp
<div class="mb-6">
    {{-- Group header --}}
    <div class="flex items-center justify-between px-4 py-2 rounded-t-lg bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
        <div class="flex items-center gap-3">
            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $pmLabel }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $pmCount }} payment{{ $pmCount !== 1 ? 's' : '' }}</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span class="text-gray-500 dark:text-gray-400 text-xs">
                Base: {{ \App\Helpers\CustomHelper::formatCurrency($pmBase) }}
                &nbsp;|&nbsp; Tax: {{ \App\Helpers\CustomHelper::formatCurrency($pmTax) }}
            </span>
            <span class="font-bold text-gray-900 dark:text-white">{{ \App\Helpers\CustomHelper::formatCurrency($pmTotal) }}</span>
        </div>
    </div>
    {{-- Group rows --}}
    <div class="overflow-x-auto border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-lg">
    <table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
    <thead class="bg-gray-50 dark:bg-gray-750 text-gray-400 text-xs">
    <tr>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Payment Date</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Order #</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Orig. Order Date</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Customer</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Revenue Source</th>
        <th class="px-3 py-1.5 text-left whitespace-nowrap">Status</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap">Base</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap">Tax</th>
        <th class="px-3 py-1.5 text-right whitespace-nowrap font-medium">Total</th>
        <th class="px-3 py-1.5 text-left">Included Because</th>
    </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
    @foreach ($pmRows->sortByDesc('payment_date') as $row)
    @php $isNeg = $row->grand_total < 0; $cls = $isNeg ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'; @endphp
    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
        <td class="px-3 py-1.5 font-mono text-gray-500">
            {{ $row->payment_date ? \Carbon\Carbon::parse($row->payment_date)->format('M j, Y') : '—' }}
        </td>
        <td class="px-3 py-1.5">
            @if ($row->order_unique_id)
                <a href="{{ route('admin.order-management.orders.edit', $row->order_unique_id) }}"
                   class="text-brand-500 hover:underline font-medium">{{ $row->order_number }}</a>
            @else
                <span class="text-gray-400">{{ $row->order_number }}</span>
            @endif
        </td>
        <td class="px-3 py-1.5 font-mono text-gray-400">
            {{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('M j, Y') : '—' }}
        </td>
        <td class="px-3 py-1.5 max-w-[140px] truncate" title="{{ $row->customer_name }}">{{ $row->customer_name }}</td>
        <td class="px-3 py-1.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                {{ match($row->stream) {
                    'refund'  => 'bg-red-50 text-red-700',
                    'account' => 'bg-emerald-50 text-emerald-700',
                    'billing' => 'bg-purple-50 text-purple-700',
                    default   => 'bg-blue-50 text-blue-700',
                } }}">
                {{ $row->revenue_source }}
            </span>
        </td>
        <td class="px-3 py-1.5">
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs
                {{ str_contains(strtolower($row->payment_status ?? ''), 'refund') ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-700' }}">
                {{ $row->payment_status }}
            </span>
        </td>
        <td class="px-3 py-1.5 text-right {{ $cls }}">{{ \App\Helpers\CustomHelper::formatCurrency($row->base_amount) }}</td>
        <td class="px-3 py-1.5 text-right text-amber-500">
            {{ $row->tax_amount != 0 ? \App\Helpers\CustomHelper::formatCurrency($row->tax_amount) : '—' }}
        </td>
        <td class="px-3 py-1.5 text-right font-semibold {{ $cls }}">{{ \App\Helpers\CustomHelper::formatCurrency($row->grand_total) }}</td>
        <td class="px-3 py-1.5 text-gray-400 max-w-[220px] truncate">
            {{ $row->included_because }}
            @if ($row->stream === 'refund')
                @include('admin.reports.sales_reports.pure_sales_summary.partials._ledger_refund_detail', ['row' => $row])
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <tr>
        <td colspan="6" class="px-3 py-1.5 text-right text-xs font-semibold text-gray-500 uppercase">
            Subtotal — {{ $pmLabel }}
        </td>
        <td class="px-3 py-1.5 text-right text-xs font-semibold text-gray-900 dark:text-white">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmBase) }}
        </td>
        <td class="px-3 py-1.5 text-right text-xs font-semibold text-amber-600">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmTax) }}
        </td>
        <td class="px-3 py-1.5 text-right font-bold text-gray-900 dark:text-white">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmTotal) }}
        </td>
        <td></td>
    </tr>
    </tfoot>
    </table>
    </div>
</div>
@endforeach

{{-- Grand total --}}
<div class="mt-2 flex justify-end">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-6 py-3 flex items-center gap-8">
        <span class="text-sm text-gray-600 dark:text-gray-300">{{ number_format($rowCount) }} payments</span>
        <div class="text-right">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Grand Total</div>
            <div class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ \App\Helpers\CustomHelper::formatCurrency($grandTotal) }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── PAYMENT METHOD TOTALS (always shown) ─────────────────────────────────── --}}
<div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
        Payment Method Totals
    </h4>
    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
    <thead>
    <tr class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
        <th class="pb-2 text-left pr-6">Payment Method</th>
        <th class="pb-2 text-right pr-4">Count</th>
        <th class="pb-2 text-right pr-4">Base Amount</th>
        <th class="pb-2 text-right pr-4">Tax Collected</th>
        <th class="pb-2 text-right">Total Collected</th>
    </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
    @foreach ($pmGroups as $pmKey => $pmGrp)
    @php
        $pmGrandTotal = $pmGrp->sum('grand_total');
        $pmTotalCls   = $pmGrandTotal < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white';
    @endphp
    <tr>
        <td class="py-2 pr-6 font-medium text-gray-700 dark:text-gray-300">
            {{ $pmGrp->first()->payment_method }}
        </td>
        <td class="py-2 pr-4 text-right text-gray-500 dark:text-gray-400">
            {{ number_format($pmGrp->count()) }}
        </td>
        <td class="py-2 pr-4 text-right {{ $pmTotalCls }}">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmGrp->sum('base_amount')) }}
        </td>
        <td class="py-2 pr-4 text-right text-amber-600 dark:text-amber-400">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmGrp->sum('tax_amount')) }}
        </td>
        <td class="py-2 text-right font-semibold {{ $pmTotalCls }}">
            {{ \App\Helpers\CustomHelper::formatCurrency($pmGrandTotal) }}
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
    <tr>
        <td class="pt-3 pr-6 font-bold text-gray-900 dark:text-white uppercase text-xs tracking-wide">
            Grand Total Collected
        </td>
        <td class="pt-3 pr-4 text-right font-semibold text-gray-600 dark:text-gray-300">
            {{ number_format($rowCount) }}
        </td>
        <td class="pt-3 pr-4 text-right font-bold text-gray-900 dark:text-white">
            {{ \App\Helpers\CustomHelper::formatCurrency($totalBase) }}
        </td>
        <td class="pt-3 pr-4 text-right font-bold text-amber-600 dark:text-amber-400">
            {{ \App\Helpers\CustomHelper::formatCurrency($totalTax) }}
        </td>
        <td class="pt-3 text-right text-xl font-bold text-blue-700 dark:text-blue-300">
            {{ \App\Helpers\CustomHelper::formatCurrency($grandTotal) }}
        </td>
    </tr>
    </tfoot>
    </table>
    </div>
</div>

@endif {{-- end !empty --}}
