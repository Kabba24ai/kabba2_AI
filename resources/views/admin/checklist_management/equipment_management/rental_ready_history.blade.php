@extends('admin.layouts.app')

@section('title', 'Rental Ready History')

@php
    use App\Enums\ChecklistManagement\RentalReadyLifecycleStatus;
    use App\Enums\ChecklistManagement\RentalReadyResult;

    $lifecycleTone = [
        'draft'      => 'bg-gray-100 text-gray-700 border-gray-200',
        'completed'  => 'bg-green-50 text-green-700 border-green-200',
        'voided'     => 'bg-red-50 text-red-700 border-red-200',
        'superseded' => 'bg-amber-50 text-amber-800 border-amber-200',
        'abandoned'  => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $resultTone = [
        'rental_ready'     => 'bg-green-50 text-green-700 border-green-200',
        'maintenance_hold' => 'bg-amber-50 text-amber-800 border-amber-200',
        'damaged'          => 'bg-red-50 text-red-700 border-red-200',
    ];
@endphp

@section('content')
    @include('flash::message')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rental Ready Inspection History</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $equipment->equipment_name }}
                <span class="text-gray-400">· #{{ $equipment->equipment_id }}</span>
            </p>
        </div>
        <a href="{{ route('admin.checklist-management.equipment-management.show', $equipment->unique_id) }}"
            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            <x-heroicon-o-arrow-left class="w-4 h-4" /> Back to Inspection
        </a>
    </div>

    <div class="bg-white rounded-md shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr class="text-left">
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Inspector</th>
                        <th class="px-4 py-3 font-medium text-right">Hours</th>
                        <th class="px-4 py-3 font-medium">Lifecycle</th>
                        <th class="px-4 py-3 font-medium">Result</th>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Completed</th>
                        <th class="px-4 py-3 font-medium text-center" title="Answered / total questions">Questions</th>
                        <th class="px-4 py-3 font-medium text-center" title="Maintenance findings">Maint.</th>
                        <th class="px-4 py-3 font-medium text-center" title="Damage findings">Damage</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($inspections as $ins)
                        @php
                            $lc = $ins->lifecycle_status instanceof RentalReadyLifecycleStatus ? $ins->lifecycle_status->value : (string) $ins->lifecycle_status;
                            $rs = $ins->result instanceof RentalReadyResult ? $ins->result : null;
                            $order = $ins->orderProduct?->order ?? $ins->order;
                            $orderNo = $order?->order_number;
                            $orderUrl = $order
                                ? route('admin.order-management.orders.edit', ['unique_id' => $order->unique_id])
                                : null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::parse($ins->inspection_date)->format('M j, Y') }}
                                @if ($ins->inspection_time)
                                    <span class="text-gray-400">· {{ \Illuminate\Support\Carbon::parse($ins->inspection_time)->format('g:i A') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $ins->employee_name ?: optional($ins->employee)->full_name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $ins->equipment_hours !== null ? rtrim(rtrim(number_format((float) $ins->equipment_hours, 1), '0'), '.') : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-medium {{ $lifecycleTone[$lc] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                    {{ ucfirst($lc) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($rs)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-medium {{ $resultTone[$rs->value] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                        {{ $rs->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">— in progress</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                @if ($orderNo)
                                    @php $orderLabel = '#' . ltrim((string) $orderNo, '#'); @endphp
                                    @if ($orderUrl)
                                        <a href="{{ $orderUrl }}" class="text-sky-700 hover:underline font-medium">{{ $orderLabel }}</a>
                                    @else
                                        {{ $orderLabel }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $ins->completed_at ? $ins->completed_at->format('M j, Y g:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ (int) $ins->required_items_completed }}/{{ (int) $ins->total_questions }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ((int) $ins->items_requiring_maintenance > 0)
                                    <span class="text-amber-700 font-semibold">{{ (int) $ins->items_requiring_maintenance }}</span>
                                @else
                                    <span class="text-gray-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ((int) $ins->damaged_items > 0)
                                    <span class="text-red-700 font-semibold">{{ (int) $ins->damaged_items }}</span>
                                @else
                                    <span class="text-gray-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipment->unique_id, $ins->unique_id]) }}"
                                    class="inline-flex items-center gap-1 text-sky-700 hover:underline font-medium">
                                    View Inspection <x-heroicon-o-chevron-right class="w-3.5 h-3.5" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center text-gray-400">No Rental Ready inspections recorded for this equipment yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($inspections->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $inspections->links() }}
            </div>
        @endif
    </div>
@endsection
