@extends('admin.layouts.app')

@section('title', 'Authorize.Net Reconciliation')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
    .anr-input { width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; background: #fff; }
    .anr-label { display:block; font-size: 0.78rem; font-weight: 600; color:#374151; margin-bottom: 0.25rem; }
    .anr-tile { border:1px solid #e5e7eb; border-radius:0.75rem; background:#fff; padding:0.75rem 1rem; }
    .anr-tile .n { font-size:1.35rem; font-weight:700; color:#111827; }
    .anr-tile .l { font-size:0.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em; }
    .anr-banner { border-radius:0.75rem; padding:0.85rem 1.15rem; font-weight:600; font-size:0.95rem; }
    .anr-green  { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
    .anr-yellow { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
    .anr-red    { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    .anr-chip { display:inline-block; padding:0.35rem 0.7rem; border-radius:9999px; font-size:0.78rem; font-weight:500; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer; }
    .anr-chip.active { background:#2563eb; border-color:#2563eb; color:#fff; }
    .anr-badge { display:inline-block; padding:0.15rem 0.55rem; border-radius:9999px; font-size:0.72rem; font-weight:600; white-space:nowrap; }
    .anr-b-green  { background:#dcfce7; color:#166534; }
    .anr-b-red    { background:#fee2e2; color:#991b1b; }
    .anr-b-yellow { background:#fef9c3; color:#854d0e; }
    .anr-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
    .anr-table th, .anr-table td { text-align:left; padding:0.5rem 0.6rem; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
    .anr-table th { color:#6b7280; text-transform:uppercase; font-size:0.68rem; letter-spacing:.03em; }
    .anr-scroll { overflow-x:auto; }
</style>
@endpush

@section('content')
    @include('flash::message')

    @php
        // Status → badge palette (green clean, red hard problem, yellow warning).
        $badge = function (string $s): string {
            $red    = ['Amount Mismatch','Missing in Kabba','Missing in Authorize.Net','Duplicate Transaction','Refund Mismatch'];
            $yellow = ['Tax Difference','Parent/Child Conflict','Deleted Extension','Manual Review Required','Unclassified'];
            if ($s === 'Exact Match') return 'anr-b-green';
            if (in_array($s, $red, true)) return 'anr-b-red';
            if (in_array($s, $yellow, true)) return 'anr-b-yellow';
            return 'anr-b-yellow';
        };
    @endphp

    <div class="max-w-7xl mx-auto">
        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-gray-900">Authorize.Net Reconciliation</h1>
            <p class="text-sm text-gray-500 mt-1">
                Reconciles Kabba payment records against the Authorize.Net gateway. The export's leading columns match
                the Authorize.Net transaction download exactly. Upload an Authorize.Net export to cross-match
                transaction-by-transaction. This report is read-only.
            </p>
        </div>

        {{-- Single form carries the params + optional gateway file; the Run,
             filter-chip, and Download buttons re-submit it via formaction. --}}
        <form method="POST" action="{{ route('admin.reports.payment-reconciliation.index') }}"
              enctype="multipart/form-data" id="anr-form">
            @csrf
            <input type="hidden" name="date_range" value="custom">

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="anr-label">Start Date</label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="anr-input">
                    </div>
                    <div>
                        <label class="anr-label">End Date</label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="anr-input">
                    </div>
                    <div>
                        <label class="anr-label">Store (optional)</label>
                        <select name="store" class="anr-input">
                            <option value="">All stores</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $filters['store'] === (string) $store->id)>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="anr-label">Authorize.Net Export (optional)</label>
                        <input type="file" name="gateway_file" accept=".txt,.csv,.tsv" class="anr-input">
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-3 mt-4">
                    <button type="submit" name="filter" value="{{ $activeFilter }}"
                        formaction="{{ route('admin.reports.payment-reconciliation.index') }}"
                        class="px-5 py-2.5 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                        Run Reconciliation
                    </button>
                    <button type="submit"
                        formaction="{{ route('admin.reports.payment-reconciliation.export') }}"
                        class="px-5 py-2.5 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        Download Full CSV
                    </button>
                    <button type="submit" name="exceptions" value="1"
                        formaction="{{ route('admin.reports.payment-reconciliation.export') }}"
                        class="px-5 py-2.5 rounded-lg font-medium text-sm border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100">
                        Download Exceptions Only
                    </button>
                </div>
            </div>

            @if ($hasRun)
                {{-- Health banner --}}
                <div class="anr-banner anr-{{ $summary['health'] }} mb-4">
                    Authorize.Net Reconciliation Status — {{ $summary['banner'] }}
                </div>

                {{-- Dashboard summary (card border tinted by health) --}}
                <div class="rounded-xl border p-4 mb-5 {{ $summary['health'] === 'red' ? 'border-red-200 bg-red-50/40' : ($summary['health'] === 'yellow' ? 'border-amber-200 bg-amber-50/40' : 'border-emerald-200 bg-emerald-50/40') }}">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <div class="anr-tile"><div class="n">{{ number_format($summary['total_gateway']) }}</div><div class="l">Gateway Transactions</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['total_kabba']) }}</div><div class="l">Kabba Transactions</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['exact_matches']) }}</div><div class="l">Exact Matches</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['amount_mismatches']) }}</div><div class="l">Amount Mismatches</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['missing_in_kabba']) }}</div><div class="l">Missing in Kabba</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['missing_in_authorizenet']) }}</div><div class="l">Missing in Authorize.Net</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['duplicate_transaction_ids']) }}</div><div class="l">Duplicate Transaction IDs</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['refund_mismatches']) }}</div><div class="l">Refund Mismatches</div></div>
                        <div class="anr-tile"><div class="n">{{ number_format($summary['manual_review_required']) }}</div><div class="l">Manual Review Required</div></div>
                    </div>
                </div>

                {{-- Quick filters (each re-submits the form with its filter key) --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($filterOptions as $key => $label)
                        <button type="submit" name="filter" value="{{ $key }}"
                            formaction="{{ route('admin.reports.payment-reconciliation.index') }}"
                            class="anr-chip {{ $activeFilter === $key ? 'active' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Results table (compact; full detail is in the CSV) --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-2 anr-scroll">
                    <table class="anr-table">
                        <thead>
                            <tr>
                                <th>Status</th><th>Conf.</th><th>Transaction ID</th><th>Date</th>
                                <th>Invoice / Order</th><th>Customer</th><th>Action</th>
                                <th>Settlement</th><th>Kabba Signed</th><th>Amt Diff</th><th>Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td><span class="anr-badge {{ $badge($r['Reconciliation Status']) }}">{{ $r['Reconciliation Status'] }}</span></td>
                                    <td>{{ $r['Match Confidence'] }}</td>
                                    <td>{{ $r['Transaction ID'] }}</td>
                                    <td>{{ $r['Submit Date/Time'] ?: $r['Kabba Payment Date'] }}</td>
                                    <td>{{ $r['Invoice Number'] ?: $r['Kabba Order Number'] }}</td>
                                    <td>{{ trim(($r['Customer First Name'] ?? '') . ' ' . ($r['Customer Last Name'] ?? '')) ?: '—' }}</td>
                                    <td>{{ $r['Action Code'] }}</td>
                                    <td>{{ $r['Settlement Amount'] }}</td>
                                    <td>{{ $r['Kabba Signed Amount'] }}</td>
                                    <td>{{ $r['Amount Difference'] }}</td>
                                    <td>{{ $r['Manual Review Required'] === 'Yes' ? '⚠︎' : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center text-gray-400 py-6">No transactions for this selection.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-white rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                    Choose a date range (and optionally upload an Authorize.Net export) and click
                    <span class="font-medium text-gray-700">Run Reconciliation</span>.
                </div>
            @endif
        </form>
    </div>
@endsection
