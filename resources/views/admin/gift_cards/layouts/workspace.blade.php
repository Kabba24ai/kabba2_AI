{{--
    Shared shell for every gift card screen: page heading, tab strip and the
    small set of styles the workspace uses.

    One shell rather than seven copies so the tabs cannot drift apart, and so
    a new screen inherits the workspace rather than reinventing it.
--}}
@extends('admin.layouts.app')

@section('title', $pageTitle ?? 'Gift Cards')

@push('css')
    <style>
        main { background-color: #f8fafc; flex: 1 1 auto; }

        .gc-card      { background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; }
        .gc-input     { width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.5rem 0.75rem; font-size:0.875rem; background:#fff; }
        .gc-input:focus { outline:2px solid #c7d2fe; outline-offset:-1px; border-color:#818cf8; }
        .gc-label     { display:block; font-size:0.78rem; font-weight:600; color:#374151; margin-bottom:0.25rem; }
        .gc-hint      { font-size:0.72rem; color:#6b7280; margin-top:0.25rem; }

        /* KPI tile. `n` is the figure, `l` the label, `s` the sub-note. */
        .gc-tile      { border:1px solid #e5e7eb; border-radius:0.75rem; background:#fff; padding:0.85rem 1rem; }
        .gc-tile .n   { font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2; }
        .gc-tile .l   { font-size:0.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
        .gc-tile .s   { font-size:0.7rem; color:#9ca3af; margin-top:0.15rem; }

        .gc-table     { width:100%; border-collapse:collapse; font-size:0.8rem; }
        .gc-table th, .gc-table td { text-align:left; padding:0.55rem 0.65rem; border-bottom:1px solid #f1f5f9; }
        .gc-table th  { color:#6b7280; text-transform:uppercase; font-size:0.68rem; letter-spacing:.03em; white-space:nowrap; }
        .gc-table tbody tr:hover { background:#f9fafb; }
        .gc-scroll    { overflow-x:auto; }
        .gc-num       { font-variant-numeric: tabular-nums; white-space:nowrap; }
        .gc-mono      { font-family: ui-monospace, Menlo, Consolas, monospace; }

        .gc-badge     { display:inline-block; padding:0.15rem 0.5rem; border-radius:9999px; font-size:0.68rem; font-weight:600; white-space:nowrap; }

        .gc-tabs      { display:flex; gap:0.25rem; border-bottom:1px solid #e5e7eb; overflow-x:auto; }
        .gc-tab       { padding:0.6rem 0.9rem; font-size:0.85rem; font-weight:500; color:#6b7280; border-bottom:2px solid transparent; white-space:nowrap; }
        .gc-tab:hover { color:#111827; }
        .gc-tab-on    { color:#4f46e5; border-bottom-color:#4f46e5; font-weight:600; }

        .gc-btn       { display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 0.85rem; border-radius:0.5rem; font-size:0.82rem; font-weight:600; border:1px solid transparent; cursor:pointer; }
        .gc-btn-primary { background:#4f46e5; color:#fff; }
        .gc-btn-primary:hover { background:#4338ca; color:#fff; }
        .gc-btn-ghost { background:#fff; color:#374151; border-color:#d1d5db; }
        .gc-btn-ghost:hover { background:#f9fafb; }
        .gc-btn-danger { background:#fff; color:#b91c1c; border-color:#fca5a5; }
        .gc-btn-danger:hover { background:#fef2f2; }

        .gc-empty     { text-align:center; padding:2.5rem 1rem; color:#6b7280; font-size:0.875rem; }
    </style>
    @stack('gc-css')
@endpush

@section('content')
    @include('flash::message')

    <div class="max-w-7xl mx-auto">

        <div class="mb-4 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">@yield('gc-heading', 'Gift Cards')</h1>
                <p class="text-sm text-gray-500 mt-1 max-w-3xl">@yield('gc-subheading')</p>
            </div>
            <div class="flex items-center gap-2">@yield('gc-actions')</div>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Please correct the following:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('admin.gift_cards.partials._tabs')

        <div class="mt-5">
            @yield('gc-body')
        </div>
    </div>
@endsection
