@extends('admin.layouts.app')

@section('title', 'Service Operations')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
    [data-svc-handle]:active { cursor: grabbing; }
    .svc-ghost { opacity: .4; }
    [data-svc-lane]::-webkit-scrollbar { height: 9px; }
    [data-svc-lane]::-webkit-scrollbar-thumb { background: rgba(15,23,42,.18); border-radius: 999px; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div style="padding: 4px 2px 10px;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 22px;">
            <div>
                <h1 style="font-size: 26px; font-weight: 700; margin: 0 0 4px; color: #0f172a;">Service Operations</h1>
                <p style="font-size: 14px; color: #64748b; margin: 0;">{{ now()->format('l, F j, Y') }} · The complete shop &amp; field workload, by technician.</p>
            </div>

            {{-- New Service Work — the single launch point for every work type.
                 Each choice opens its existing specialized intake. --}}
            <div x-data="{ open: false }" style="position: relative; flex: 0 0 auto;">
                <button type="button" @click="open = !open" @click.outside="open = false"
                        style="display: inline-flex; align-items: center; gap: 8px; background: #0d9488; color: #fff; border: none; border-radius: 9px; padding: 10px 18px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                    <span style="font-size: 16px; line-height: 0;">+</span> New Service Work
                    <span style="font-size: 11px; opacity: .85;">▾</span>
                </button>
                <div x-show="open" x-cloak x-transition
                     style="position: absolute; right: 0; top: calc(100% + 6px); width: 232px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 30px rgba(15,23,42,.12); padding: 6px; z-index: 50;">
                    <a href="{{ route('admin.service-management.tickets.create') }}" style="display: block; padding: 9px 12px; border-radius: 7px; text-decoration: none; color: #0f172a; font-size: 13.5px; font-weight: 600;">Shop Repair<span style="display:block; font-weight:400; font-size:12px; color:#64748b;">Standard in-shop service ticket</span></a>
                    <a href="{{ route('admin.field-service.tickets.create') }}" style="display: block; padding: 9px 12px; border-radius: 7px; text-decoration: none; color: #0f172a; font-size: 13.5px; font-weight: 600;">Field Service<span style="display:block; font-weight:400; font-size:12px; color:#64748b;">On-site mission w/ dispatch</span></a>
                    @if (Route::has('admin.warranty.claims.create'))
                        <a href="{{ route('admin.warranty.claims.create') }}" style="display: block; padding: 9px 12px; border-radius: 7px; text-decoration: none; color: #0f172a; font-size: 13.5px; font-weight: 600;">Warranty Claim<span style="display:block; font-weight:400; font-size:12px; color:#64748b;">OEM claim + linked repair</span></a>
                    @endif
                </div>
            </div>
        </div>

        <livewire:service-management.operations-board />
    </div>

@endsection
