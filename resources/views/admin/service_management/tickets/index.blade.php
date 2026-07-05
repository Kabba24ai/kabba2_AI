@extends('admin.layouts.app')

@section('title', 'Service Tickets')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Service\FinancialResponsibility;
        use App\Enums\Service\FinancialStatus;
        use App\Enums\Service\RepairStatus;
        use App\Enums\Service\ServicePriority;
        use App\Enums\Service\ServiceType;
        $inputClass = 'border border-gray-300 rounded-lg bg-white text-sm py-2 px-3 focus:ring focus:border-blue-400 outline-none';
    @endphp

    {{-- ===== Header / filter card ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-ticket class="w-6 h-6 text-blue-600" />
                Service Tickets
            </h1>
            <a href="{{ route('admin.service-management.tickets.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition">
                <x-heroicon-o-plus class="w-4 h-4" />
                New Service Ticket
            </a>
        </div>

        <form method="GET" action="{{ route('admin.service-management.tickets.index') }}">
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.service-management.tickets.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition shrink-0">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                    Clear
                </a>

                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ticket, equipment, customer…"
                    class="{{ $inputClass }} w-56">
                <input type="text" name="order_number" value="{{ request('order_number') }}" placeholder="Order ID"
                    class="{{ $inputClass }} w-28">

                <select name="state" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All States</option>
                    <option value="active" @selected(request('state') === 'active')>Active</option>
                    <option value="blocked" @selected(request('state') === 'blocked')>Blocked</option>
                    <option value="closed" @selected(request('state') === 'closed')>Closed / Finished</option>
                </select>

                <select name="priority" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    @foreach (ServicePriority::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('priority') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="service_type" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach (ServiceType::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('service_type') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="repair_status" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach (RepairStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('repair_status') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="financial_responsibility" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Responsibility</option>
                    @foreach (FinancialResponsibility::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('financial_responsibility') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="financial_status" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Financial</option>
                    @foreach (FinancialStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('financial_status') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select name="technician" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">All Technicians</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(request('technician') == $employee->id)>
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </option>
                    @endforeach
                </select>

                <select name="equipment" class="{{ $inputClass }} max-w-52" onchange="this.form.submit()">
                    <option value="">All Equipment</option>
                    @foreach ($equipmentList as $eq)
                        <option value="{{ $eq->id }}" @selected(request('equipment') == $eq->id)>{{ $eq->equipment_name }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ request('date_from') }}" class="{{ $inputClass }}" title="Opened from">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="{{ $inputClass }}" title="Opened to">

                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shrink-0">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- ===== Tickets table ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                    <tr>
                        <th class="py-3 px-4 text-left">Ticket #</th>
                        <th class="py-3 px-4 text-left">Priority</th>
                        <th class="py-3 px-4 text-left">Type</th>
                        <th class="py-3 px-4 text-left">Equipment</th>
                        <th class="py-3 px-4 text-left">Customer / Order</th>
                        <th class="py-3 px-4 text-left">Personnel</th>
                        <th class="py-3 px-4 text-left">Repair Status</th>
                        <th class="py-3 px-4 text-left">Responsibility</th>
                        <th class="py-3 px-4 text-left">Financial</th>
                        <th class="py-3 px-4 text-center">Age</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.service-management.tickets.show', $ticket) }}"
                                    class="font-semibold text-blue-600 hover:underline">{{ $ticket->ticket_number }}</a>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ticket->service_type->color() }}">
                                    {{ $ticket->service_type->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 max-w-[200px] truncate">{{ $ticket->equipment?->equipment_name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <div class="max-w-[180px] truncate">
                                    {{ $ticket->customer ? trim($ticket->customer->first_name . ' ' . $ticket->customer->last_name) : '—' }}
                                </div>
                                @if ($ticket->order)
                                    <div class="text-xs text-gray-400">{{ $ticket->order->order_number }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @include('admin.service_management.partials._personnel_avatars', ['people' => $ticket->personnel])
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ticket->repair_status->color() }}">
                                    {{ $ticket->repair_status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-600">{{ $ticket->financial_responsibility->label() }}</td>
                            <td class="py-3 px-4 text-xs text-gray-600">{{ $ticket->financial_status->label() }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="{{ $ticket->age_days >= 7 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $ticket->age_days }}d</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-1.5 items-center justify-center">
                                    <a href="{{ route('admin.service-management.tickets.show', $ticket) }}" title="View"
                                        class="p-1 text-sky-600 hover:text-sky-800"><x-heroicon-o-eye class="w-4 h-4" /></a>
                                    <a href="{{ route('admin.service-management.tickets.edit', $ticket) }}" title="Edit"
                                        class="p-1 text-gray-500 hover:text-gray-700"><x-heroicon-o-pencil-square class="w-4 h-4" /></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-10 px-4 text-center text-sm text-gray-400 italic">No service tickets match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

@endsection
