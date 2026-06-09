@extends('admin.layouts.app')

@section('title', 'Dispatch - Driver Workload')

@section('content')

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-users class="w-6 h-6 text-blue-600" />
            Driver Workload
        </h1>
        <a href="{{ route('admin.order-management.dispatch.index') }}"
            class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50">
            <x-heroicon-o-calendar-days class="w-4 h-4" />
            Dispatch Schedule
        </a>
    </div>

    {{-- Unassigned alert banner --}}
    @if ($unassignedDeliveries > 0 || $unassignedReturns > 0)
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex flex-wrap items-center gap-4">
        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500 shrink-0" />
        <span class="text-sm font-medium text-amber-800">Unassigned jobs need a driver:</span>
        @if ($unassignedDeliveries > 0)
            <a href="{{ route('admin.order-management.dispatch.index') }}?schedule_type[]=Delivery"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-100 text-blue-800 text-sm font-semibold hover:bg-blue-200">
                <x-heroicon-o-truck class="w-3.5 h-3.5" />
                {{ $unassignedDeliveries }} Unassigned {{ Str::plural('Delivery', $unassignedDeliveries) }}
            </a>
        @endif
        @if ($unassignedReturns > 0)
            <a href="{{ route('admin.order-management.dispatch.index') }}?schedule_type[]=Return"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-100 text-purple-800 text-sm font-semibold hover:bg-purple-200">
                <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                {{ $unassignedReturns }} Unassigned {{ Str::plural('Return', $unassignedReturns) }}
            </a>
        @endif
    </div>
    @endif

    {{-- Summary totals bar --}}
    @php
        $totalDeliveries = $drivers->sum('pending_deliveries');
        $totalReturns    = $drivers->sum('pending_returns');
        $totalAll        = $totalDeliveries + $totalReturns;
        $maxLoad         = $drivers->max('total_pending') ?: 1;
    @endphp

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <x-heroicon-o-truck class="w-5 h-5 text-blue-600" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Pending Deliveries</p>
                <p class="text-2xl font-bold text-blue-700">{{ $totalDeliveries }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                <x-heroicon-o-arrow-uturn-left class="w-5 h-5 text-purple-600" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Pending Returns</p>
                <p class="text-2xl font-bold text-purple-700">{{ $totalReturns }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-500" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Pending Jobs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalAll }}</p>
            </div>
        </div>
    </div>

    {{-- Driver cards --}}
    @if ($drivers->isEmpty())
        <div class="bg-white rounded-xl border shadow-sm p-12 text-center text-gray-400">
            <x-heroicon-o-users class="w-10 h-10 mx-auto mb-3 text-gray-300" />
            <p class="text-sm">No designated drivers found. Go to HRM &rsaquo; Users and check <em>Designate as a Driver</em> on each driver&rsquo;s profile.</p>
        </div>
    @else
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="py-3 px-5 text-left font-semibold text-gray-700">Driver</th>
                    <th class="py-3 px-5 text-center font-semibold text-blue-700">Deliveries Scheduled</th>
                    <th class="py-3 px-5 text-center font-semibold text-purple-700">Returns Scheduled</th>
                    <th class="py-3 px-5 text-center font-semibold text-gray-600">Total</th>
                    <th class="py-3 px-5 text-center font-semibold text-gray-500">Workload</th>
                    <th class="py-3 px-5 text-center font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($drivers as $driver)
                @php
                    $load    = $driver->total_pending;
                    $pct     = $maxLoad > 0 ? round(($load / $maxLoad) * 100) : 0;
                    $barColor = $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-emerald-500');
                    $initials = strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1));
                @endphp
                <tr class="hover:bg-gray-50">
                    {{-- Driver --}}
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold shrink-0">
                                {{ $initials }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $driver->full_name }}</p>
                                @if ($driver->mobile_phone ?: $driver->phone_number)
                                    <p class="text-xs text-gray-400 tabular-nums">{{ $driver->mobile_phone ?: $driver->phone_number }}</p>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Deliveries --}}
                    <td class="py-4 px-5 text-center">
                        @if ($driver->pending_deliveries > 0)
                            <a href="{{ route('admin.order-management.dispatch.index') }}?schedule_type[]=Delivery&driver_id={{ $driver->id }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-100 text-blue-800 font-semibold text-sm hover:bg-blue-200">
                                <x-heroicon-o-truck class="w-3.5 h-3.5" />
                                {{ $driver->pending_deliveries }}
                            </a>
                        @else
                            <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </td>

                    {{-- Returns --}}
                    <td class="py-4 px-5 text-center">
                        @if ($driver->pending_returns > 0)
                            <a href="{{ route('admin.order-management.dispatch.index') }}?schedule_type[]=Return&driver_id={{ $driver->id }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-100 text-purple-800 font-semibold text-sm hover:bg-purple-200">
                                <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                                {{ $driver->pending_returns }}
                            </a>
                        @else
                            <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </td>

                    {{-- Total --}}
                    <td class="py-4 px-5 text-center">
                        @if ($load > 0)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 font-bold text-sm">
                                {{ $load }}
                            </span>
                        @else
                            <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </td>

                    {{-- Workload bar --}}
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 min-w-[80px]">
                                <div class="{{ $barColor }} h-2 rounded-full transition-all"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 tabular-nums w-8 text-right">{{ $pct }}%</span>
                        </div>
                    </td>

                    {{-- Actions --}}
                    <td class="py-4 px-5 text-center">
                        <a href="{{ route('admin.order-management.dispatch.index') }}?driver_id={{ $driver->id }}"
                           class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium border border-blue-200 rounded-md px-3 py-1.5 bg-blue-50 hover:bg-blue-100">
                            <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                            View Schedule
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

@endsection
