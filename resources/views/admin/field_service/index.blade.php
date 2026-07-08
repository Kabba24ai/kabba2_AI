@extends('admin.layouts.app')

@section('title', 'Field Service')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
                Field Service
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Field missions — technicians dispatched to assess and stabilize customer-site incidents.
            </p>
        </div>
        <a href="{{ route('admin.field-service.tickets.create') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 shadow-sm transition">
            <x-heroicon-o-plus class="w-4 h-4" />
            New Field Ticket
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search ticket #, customer, equipment, order, site…"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring focus:border-blue-400 outline-none">
            </div>
            <div>
                <select name="mission_status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">Active missions</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}" @selected(request('mission_status') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="priority" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $case)
                        <option value="{{ $case->value }}" @selected(request('priority') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                    Filter
                </button>
                <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer">
                    <input type="checkbox" name="include_closed" value="1" @checked(request()->boolean('include_closed'))
                        class="text-blue-600 rounded focus:ring-blue-500" onchange="this.form.submit()">
                    Include closed
                </label>
            </div>
        </div>
    </form>

    {{-- Tickets --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                        <th class="px-4 py-3">Ticket</th>
                        <th class="px-4 py-3">Mission Status</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Customer / Order</th>
                        <th class="px-4 py-3">Equipment</th>
                        <th class="px-4 py-3">Job Site</th>
                        <th class="px-4 py-3">Technician</th>
                        <th class="px-4 py-3">Reported</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.field-service.tickets.show', $ticket) }}"
                                    class="font-semibold text-blue-600 hover:text-blue-700">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ticket->mission_status->color() }}">
                                    {{ $ticket->mission_status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-800">{{ $ticket->customer?->full_name ?? $ticket->order?->customer_name ?? '—' }}</p>
                                @if ($ticket->order)
                                    <p class="text-xs text-gray-400">{{ $ticket->order->order_number }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $ticket->equipment?->equipment_name ?? '—' }}
                                @if ($ticket->equipment?->equipment_id)
                                    <span class="text-xs text-gray-400">({{ $ticket->equipment->equipment_id }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-[240px] truncate" title="{{ $ticket->job_site_address }}">
                                {{ $ticket->job_site_address }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $ticket->technician ? $ticket->technician->first_name . ' ' . $ticket->technician->last_name : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $ticket->reported_at->format('M j, g:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <x-heroicon-o-truck class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                                <p class="text-sm text-gray-500 font-medium">No field service tickets{{ request()->hasAny(['search', 'mission_status', 'priority']) ? ' match these filters' : ' yet' }}.</p>
                                <p class="text-xs text-gray-400 mt-1">Create one when a technician needs to be dispatched to a customer site.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

@endsection
