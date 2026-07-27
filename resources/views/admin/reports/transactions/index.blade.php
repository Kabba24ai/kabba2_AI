@extends('admin.layouts.app')

@section('title', 'Transaction Report')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
    .tr-input { width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; background: #fff; }
    .tr-label { display:block; font-size: 0.78rem; font-weight: 600; color:#374151; margin-bottom: 0.25rem; }
    .tr-tile { border:1px solid #e5e7eb; border-radius:0.75rem; background:#fff; padding:0.75rem 1rem; }
    .tr-tile .n { font-size:1.35rem; font-weight:700; color:#111827; }
    .tr-tile .l { font-size:0.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em; }
    .tr-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
    .tr-table th, .tr-table td { text-align:left; padding:0.5rem 0.6rem; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
    .tr-table th { color:#6b7280; text-transform:uppercase; font-size:0.68rem; letter-spacing:.03em; }
    .tr-scroll { overflow-x:auto; }
</style>
@endpush

@section('content')
    @include('flash::message')

    <div class="max-w-7xl mx-auto">
        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-gray-900">Transaction Report</h1>
            <p class="text-sm text-gray-500 mt-1">
                A financial listing of transactions for the selected period — one row per charge, refund, or void.
                View online or export to CSV/Excel.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.reports.transactions.index') }}"
              class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="tr-label">Start Date</label>
                    <input type="text" name="start_date" value="{{ $filters['start_date'] }}"
                           class="tr-input datepicker" autocomplete="off" placeholder="MM/DD/YYYY">
                </div>
                <div>
                    <label class="tr-label">End Date</label>
                    <input type="text" name="end_date" value="{{ $filters['end_date'] }}"
                           class="tr-input datepicker" autocomplete="off" placeholder="MM/DD/YYYY">
                </div>
                <div>
                    <label class="tr-label">Store (optional)</label>
                    <select name="store" class="tr-input">
                        <option value="">All stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) $filters['store'] === (string) $store->id)>{{ $store->store_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-3 mt-4">
                <button type="submit"
                    class="px-5 py-2.5 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                    Run Report
                </button>
                <a href="{{ route('admin.reports.transactions.export', request()->only('start_date', 'end_date', 'store')) }}"
                    class="px-5 py-2.5 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 {{ $hasRun ? '' : 'pointer-events-none opacity-50' }}">
                    Export CSV / Excel
                </a>
            </div>
        </form>

        @if ($hasRun)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
                <div class="tr-tile"><div class="n">{{ number_format($totals['count']) }}</div><div class="l">Transactions</div></div>
                <div class="tr-tile"><div class="n">${{ number_format($totals['net_collected'], 2) }}</div><div class="l">Net Collected</div></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-2 tr-scroll">
                <table class="tr-table">
                    <thead>
                        <tr>@foreach ($columns as $col)<th>{{ $col }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>@foreach ($columns as $col)<td>{{ $r[$col] ?? '' }}</td>@endforeach</tr>
                        @empty
                            <tr><td colspan="{{ count($columns) }}" class="text-center text-gray-400 py-6">No transactions for this selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                Choose a date range and click <span class="font-medium text-gray-700">Run Report</span>.
            </div>
        @endif
    </div>
@endsection

@push('js')
<script>
    // Drives the shared air-datepicker (.datepicker) date format — same as the
    // other reports. Must be set before the global picker init (DOMContentLoaded).
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>
@endpush
