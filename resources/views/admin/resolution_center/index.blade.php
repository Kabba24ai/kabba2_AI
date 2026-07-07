@extends('admin.layouts.app')

@section('title', 'Resolution Center')

@section('content')

    @include('flash::message')

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-900">Resolution Center — History</h2>
        @can('resolution_center.view_audit_history')
        <a href="{{ route('admin.resolution-center.operations') }}" class="text-sm text-blue-600 hover:underline">Operations Center</a>
        @endcan
    </div>

    <div class="shadow rounded-2xl overflow-x-auto border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                <tr>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Customer</th>
                    <th class="py-3 px-4">Order</th>
                    <th class="py-3 px-4">Issue</th>
                    <th class="py-3 px-4">Recommended</th>
                    <th class="py-3 px-4">Decision</th>
                    <th class="py-3 px-4">Outcome</th>
                    <th class="py-3 px-4">User</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($cases as $case)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('admin.resolution-center.show', $case->unique_id) }}'">
                        <td class="py-3 px-4">{{ $case->created_at->format('M d, Y') }}</td>
                        <td class="py-3 px-4">{{ optional($case->customer)->first_name }} {{ optional($case->customer)->last_name }}</td>
                        <td class="py-3 px-4">#{{ optional($case->order)->order_number }}</td>
                        <td class="py-3 px-4 max-w-xs truncate">{{ $case->issue }}</td>
                        <td class="py-3 px-4">
                            @if($case->recommended_resolution)
                                @foreach(explode(',', $case->recommended_resolution) as $key)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ \App\Services\ResolutionCenter\ResolutionPolicy::LABELS[$key] ?? $key }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-400">Pending</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $case->employee_decision ? ucfirst(str_replace('_', ' ', $case->employee_decision)) : '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                {{ $case->outcome === 'completed' ? 'bg-green-100 text-green-700' : ($case->outcome === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($case->outcome) }}
                            </span>
                        </td>
                        <td class="py-3 px-4">{{ optional($case->responsiblePerson)->full_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 px-4 text-center text-gray-400">No resolution cases yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $cases->links() }}
    </div>

@endsection
