@extends('admin.layouts.app')

@section('title', 'Warranty Claims')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php use App\Enums\Warranty\WarrantyQueue; @endphp

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-shield-check class="w-6 h-6 text-purple-600" />
                Warranty Claims
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Manage warranty intake, diagnostics, manufacturer submissions, approvals, reimbursements, and closeout.
            </p>
        </div>
        <a href="{{ route('admin.warranty.claims.create') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-purple-600 text-sm font-medium text-white hover:bg-purple-700 shadow-sm transition">
            <x-heroicon-o-plus class="w-4 h-4" />
            New Warranty Case
        </a>
    </div>

    {{-- KPI queue cards — filters wearing numbers --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
        @foreach (WarrantyQueue::cases() as $queue)
            @php $active = request('queue') === $queue->value; @endphp
            <a href="{{ route('admin.warranty.claims.index', $active ? [] : ['queue' => $queue->value]) }}"
                class="bg-white rounded-xl border border-gray-200 border-t-[3px] {{ $queue->accent() }} shadow-sm px-3 py-2.5 hover:shadow transition
                {{ $active ? 'ring-2 ring-purple-200' : '' }}">
                <div class="text-xl font-bold {{ $active ? 'text-purple-700' : 'text-gray-900' }}">{{ $queueCounts[$queue->value] ?? 0 }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-wide {{ $active ? 'text-purple-600' : 'text-gray-400' }} leading-tight">{{ $queue->label() }}</div>
            </a>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        @if (request('queue'))
            <input type="hidden" name="queue" value="{{ request('queue') }}">
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search case #, serial, customer, manufacturer…"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring focus:border-purple-400 outline-none">
            </div>
            <div>
                <select name="manufacturer" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">All manufacturers</option>
                    @foreach ($manufacturers as $manufacturer)
                        <option value="{{ $manufacturer }}" @selected(request('manufacturer') === $manufacturer)>{{ $manufacturer }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="path" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">Internal + External</option>
                    @foreach ($paths as $path)
                        <option value="{{ $path->value }}" @selected(request('path') === $path->value)>{{ $path->longLabel() }}</option>
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
                        class="text-purple-600 rounded focus:ring-purple-500" onchange="this.form.submit()">
                    Include closed
                </label>
            </div>
        </div>
    </form>

    {{-- Case grid --}}
    @if ($cases->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-14 text-center">
            <x-heroicon-o-shield-check class="w-10 h-10 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-500 font-medium">No warranty cases{{ request()->hasAny(['search', 'queue', 'path', 'manufacturer']) ? ' match these filters' : ' yet' }}.</p>
            <p class="text-xs text-gray-400 mt-1">Open one when a machine problem may be the manufacturer's responsibility.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($cases as $case)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <a href="{{ route('admin.warranty.claims.show', $case) }}" class="font-semibold text-purple-700 hover:text-purple-800">
                            {{ $case->case_number }}
                        </a>
                        <span class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $case->path->color() }}">{{ $case->path->label() }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $case->queue->color() }}">{{ $case->queue->label() }}</span>
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900">{{ $case->manufacturer }} · {{ $case->model }}</p>
                    <dl class="mt-2 space-y-1 text-xs flex-1">
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Serial</dt><dd class="text-gray-700 font-medium">{{ $case->serial_number }}</dd></div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400">{{ $case->customer ? 'Customer' : 'Owner' }}</dt>
                            <dd class="text-gray-700 font-medium truncate">
                                {{ $case->customer?->full_name ?? ('Internal Equipment' . ($case->equipment?->equipment_id ? ' · Unit ' . $case->equipment->equipment_id : '')) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Diagnostic fee</dt>
                            <dd><span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $case->feeStatusColor() }}">{{ $case->feeStatusLabel() }}</span></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">Service ticket</dt>
                            <dd class="font-medium">
                                @if ($case->serviceTicket)
                                    <a href="{{ route('admin.service-management.tickets.show', $case->serviceTicket) }}" class="text-blue-600 hover:text-blue-700">{{ $case->serviceTicket->ticket_number }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-gray-400">In queue</dt>
                            <dd class="font-medium {{ $case->daysInQueue() > 7 ? 'text-amber-600' : 'text-gray-700' }}">
                                {{ $case->daysInQueue() }} {{ Str::plural('day', $case->daysInQueue()) }}
                            </dd></div>
                    </dl>
                    <div class="flex items-center justify-between gap-2 mt-3 pt-2.5 border-t border-gray-100">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-amber-600">Next: {{ $case->nextRequiredAction() }}</span>
                        <a href="{{ route('admin.warranty.claims.show', $case) }}"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition shrink-0">
                            Open Case
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($cases->hasPages())
            <div class="mt-5">{{ $cases->links() }}</div>
        @endif
    @endif

@endsection
