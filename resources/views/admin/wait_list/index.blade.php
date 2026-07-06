@extends('admin.layouts.app')

@section('title', 'Equipment Wait List')

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-bell-alert class="w-6 h-6 text-blue-600" />
                    Equipment Wait List
                </h1>
                <p class="text-sm text-gray-500 mt-1.5">
                    One record = one equipment need. All customer contact and dispositions are manual.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.wait-list.alerts') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border {{ $stats['open_alerts'] ? 'border-red-300 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-600' }} text-sm font-medium hover:bg-red-100 transition">
                    <x-heroicon-o-bell-alert class="w-4 h-4" />
                    Alerts
                    @if ($stats['open_alerts'])
                        <span class="px-1.5 rounded-full bg-red-600 text-white text-xs font-bold">{{ $stats['open_alerts'] }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.wait-list.create') }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    New Wait List
                </a>
            </div>
        </div>
    </div>

    {{-- Reporting summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Waiting Now</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['waiting'] }}</p>
            @if ($stats['oldest'])
                <p class="text-xs text-gray-400 mt-1">Longest: {{ $stats['oldest']->age_days }} {{ Str::plural('day', $stats['oldest']->age_days) }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Converted</p>
            <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $stats['converted'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Top Categories</p>
            @forelse ($topCategories as $row)
                <p class="text-xs text-gray-600 mt-1">{{ $row->category?->title }} <span class="font-semibold">({{ $row->demand }})</span></p>
            @empty
                <p class="text-xs text-gray-300 italic mt-1">No category demand</p>
            @endforelse
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Top Equipment</p>
            @forelse ($topEquipment as $row)
                <p class="text-xs text-gray-600 mt-1">{{ $row->equipment_name }} <span class="font-semibold">({{ $row->demand }})</span></p>
            @empty
                <p class="text-xs text-gray-300 italic mt-1">No specific-equipment demand</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['waiting' => 'Waiting', 'active' => 'Active', 'acknowledged' => 'Acknowledged', 'converted' => 'Converted', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
                    <a href="{{ route('admin.wait-list.index', array_filter(['status' => $key, 'search' => request('search')])) }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition
                        {{ $status === $key ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer / company / phone"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white w-64">
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">Search</button>
            </form>
        </div>

        @if ($waitLists->isEmpty())
            <p class="text-sm text-gray-400 italic">No wait list records found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            <th class="pb-2 pr-3">Customer</th>
                            <th class="pb-2 pr-3">Waiting For</th>
                            <th class="pb-2 pr-3">Store Pref.</th>
                            <th class="pb-2 pr-3 text-right">Age</th>
                            <th class="pb-2 pr-3 text-right">Priority</th>
                            <th class="pb-2 pr-3">Status</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($waitLists as $waitList)
                            <tr>
                                <td class="py-2.5 pr-3">
                                    <p class="font-medium text-gray-800">{{ $waitList->customer_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $waitList->company_name ?? $waitList->phone }}</p>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <p class="text-gray-700">{{ $waitList->demandLabel() }}</p>
                                    <p class="text-[11px] text-gray-400 uppercase">{{ $waitList->request_type->label() }}</p>
                                </td>
                                <td class="py-2.5 pr-3 text-gray-600">
                                    {{ $waitList->store_preference->label() }}
                                    @if ($waitList->store)<span class="block text-xs text-gray-400">{{ $waitList->store->store_name }}</span>@endif
                                </td>
                                <td class="py-2.5 pr-3 text-right font-medium text-gray-800">{{ $waitList->age_days }}d</td>
                                <td class="py-2.5 pr-3 text-right text-gray-600">{{ $waitList->priority_override ?? '—' }}</td>
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $waitList->status->color() }}">
                                        {{ $waitList->status->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <a href="{{ route('admin.wait-list.show', $waitList) }}" class="p-1 text-sky-600 hover:text-sky-800"><x-heroicon-o-eye class="w-4 h-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $waitLists->links() }}</div>
        @endif
    </div>

@endsection
