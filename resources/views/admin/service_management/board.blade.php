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
                <p style="font-size: 14px; color: #64748b; margin: 0;">{{ now()->format('l, F j, Y') }} · Shop &amp; field tickets by technician.
                    <a href="{{ route('admin.service-management.overview') }}" style="color: #0d9488; font-weight: 600; margin-left: 6px;">Classic overview →</a>
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex: 0 0 auto;">
                <a href="{{ route('admin.service-management.tickets.create') }}"
                   style="display: inline-flex; align-items: center; gap: 7px; background: #0d9488; color: #fff; border: none; border-radius: 9px; padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none;"><span style="font-size: 16px; line-height: 0;">+</span> New Service Ticket</a>
                <a href="{{ route('admin.field-service.tickets.create') }}"
                   style="background: #fff; color: #7c3aed; border: 1px solid #e9d5ff; border-radius: 9px; padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none;">Field Service Call</a>
                <a href="{{ route('admin.service-management.tickets.index') }}"
                   style="background: #fff; color: #334155; border: 1px solid #e2e8f0; border-radius: 9px; padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none;">Search Tickets</a>
            </div>
        </div>

        <livewire:service-management.operations-board />
    </div>

@endsection
