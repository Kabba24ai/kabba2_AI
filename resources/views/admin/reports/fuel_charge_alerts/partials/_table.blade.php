@php
    $fuelStatusBadge = fn(?string $status): array => match($status) {
        'resolved'      => ['label' => 'Resolved',      'class' => 'bg-green-50 text-green-700 border-green-200'],
        'completed'     => ['label' => 'Completed',     'class' => 'bg-green-50 text-green-700 border-green-200'],
        'uncollectible' => ['label' => 'Uncollectible', 'class' => 'bg-gray-100 text-gray-500 border-gray-200'],
        default         => ['label' => 'Active',        'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
    };
@endphp

@if($records->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-10 h-10 mb-3 opacity-40" fill="currentColor" viewBox="0 0 640 640">
            <path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128z"/>
        </svg>
        <p class="text-sm">No fuel charge records found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($records as $record)
            @php
                $order     = $record->order;
                $customer  = $order?->customer;
                $equipment = $record->equipment;

                $baseFuel        = (float) ($record->fuel_total_charge ?? 0);
                $fuelAdjustments = $record->fuelChargeLogs->sum('change_amount');
                $currentFuel     = max(0, $baseFuel + $fuelAdjustments);

                $badge = $fuelStatusBadge($record->fuel_charge_status);
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition">
                <div class="flex justify-between gap-4">

                    <div class="flex-1 min-w-0">

                        {{-- Header: customer + badges --}}
                        <div class="flex items-center gap-2 flex-wrap">

                            <span class="text-sm font-medium text-gray-500">Customer:</span>
                            <h3 class="text-sm font-semibold text-gray-900">
                                {{ $customer?->full_name ?? '—' }}
                            </h3>

                            @if($customer?->phone)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                    {{ CustomHelper::formatPhone($customer->phone) }}
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold border {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>

                        </div>

                        {{-- Details block --}}
                        <div class="mt-3 border-l-4 border-orange-200 bg-orange-50/40 rounded-r-lg p-3">
                            <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">

                                <span>
                                    <span class="font-semibold text-gray-700">Order:</span>
                                    @if($order)
                                        <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                           target="_blank"
                                           class="text-blue-600 hover:underline font-medium">
                                            {{ $order->order_number ?? $order->unique_id }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </span>

                                @if($equipment)
                                    <span>
                                        <span class="font-semibold text-gray-700">Equipment:</span>
                                        <span class="text-gray-600">{{ $equipment->equipment_name }}</span>
                                    </span>
                                @endif

                                <span>
                                    <span class="font-semibold text-gray-700">Fuel Charge:</span>
                                    <span class="font-semibold text-orange-700">
                                        {{ $currentFuel > 0 ? '$' . number_format($currentFuel, 2) : 'Pending' }}
                                    </span>
                                </span>

                            </div>
                        </div>

                        {{-- Footer: date --}}
                        <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                {{ CustomHelper::formatDateTime($record->created_at) }}
                            </span>
                        </div>

                    </div>

                    {{-- Amount column --}}
                    <div class="flex flex-col items-end justify-center border-l border-gray-100 pl-4 shrink-0 min-w-[90px]">
                        <div class="text-lg font-bold text-orange-600">
                            {{ $currentFuel > 0 ? '$' . number_format($currentFuel, 2) : '—' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Fuel Charge</div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $records->links() }}
    </div>
@endif
