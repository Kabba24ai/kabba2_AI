@php
    $activeDrivers = $driverCards->filter(fn($d) => $d->delivery_jobs->isNotEmpty() || $d->return_jobs->isNotEmpty());
    $idleDrivers   = $driverCards->filter(fn($d) => $d->delivery_jobs->isEmpty() && $d->return_jobs->isEmpty());
@endphp

@if ($driverCards->isNotEmpty())

{{-- 2-column header: Column 1 = workload controls | Column 2 = idle driver strip --}}
<div class="flex items-center gap-0 mb-3">

    {{-- Column 1: Driver Workload controls --}}
    <div class="flex items-center gap-3 flex-wrap shrink-0 pr-5">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Driver Workload</span>

        {{-- Separate / Combined toggle --}}
        <div id="driver-card-view-toggle" class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
            <button type="button" id="dcv-separate"
                class="px-3 py-1.5 font-semibold bg-blue-600 text-white flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                Separate
            </button>
            <button type="button" id="dcv-combined"
                class="px-3 py-1.5 font-semibold bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Combined
            </button>
        </div>

        {{-- Show Assigned: Today Only / All --}}
        <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden text-xs">
            <span class="px-2 py-1.5 text-gray-400 font-medium border-r border-gray-300 bg-gray-50">Show:</span>
            <button type="button" id="daf-all"
                class="px-3 py-1.5 font-semibold {{ ($showAll ?? false) ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                All
            </button>
            <button type="button" id="daf-today"
                class="px-3 py-1.5 font-semibold border-l border-gray-300 {{ ($showAll ?? false) ? 'bg-white text-gray-600 hover:bg-gray-50' : 'bg-blue-600 text-white' }}">
                Today
            </button>
        </div>
        @if ($showAll ?? false)
            <span class="text-xs text-gray-400 font-medium">Showing all assigned (incl. future)</span>
        @else
            <span class="text-xs text-gray-400 font-medium">Today + past due</span>
        @endif
    </div>

    {{-- Vertical divider + Column 2: Idle drivers --}}
    @if ($idleDrivers->isNotEmpty())
    <div class="w-px self-stretch bg-gray-200 shrink-0 mx-4"></div>
    <div class="flex-1 min-w-0 flex flex-col justify-center gap-1">
        <div class="flex items-center gap-1.5">
            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Idle</span>
            <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full leading-none">{{ $idleDrivers->count() }}</span>
        </div>
        {{-- Horizontal scroll strip: 150px cards → 5 visible before scroll at typical desktop widths --}}
        <div class="flex gap-2 overflow-x-auto pb-0.5">
            @foreach ($idleDrivers as $driver)
            @php
                $initials  = strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1));
                $cdlLabels = collect(['Driver'])
                    ->when($driver->cdl_a, fn($c) => $c->push('CDL A'))
                    ->when($driver->cdl_b, fn($c) => $c->push('CDL B'));
            @endphp
            <div class="shrink-0 flex items-center gap-2 px-2.5 py-1.5 bg-white rounded-lg border border-gray-200"
                 style="min-width: 150px; max-width: 150px;">
                <div class="w-6 h-6 rounded-full bg-gray-100 text-gray-400 text-[9px] font-bold flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-600 truncate leading-tight text-[10px]">{{ $driver->full_name }}</p>
                    <p class="text-gray-400 text-[9px] truncate leading-tight">{{ $cdlLabels->implode(' · ') }}</p>
                </div>
                <span class="text-[9px] text-gray-300 italic shrink-0">idle</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>{{-- end 2-column header --}}

{{-- ===== Active driver cards grid (always 4 columns) ===== --}}
<div class="mb-4">
    <div id="driver-cards-container"
         class="grid grid-cols-4 gap-4"
         data-card-mode="separate">

        @forelse ($activeDrivers as $driver)
        @php
            $initials  = strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1));
            $totalJobs = $driver->delivery_jobs->count() + $driver->return_jobs->count();
            $wlClass   = $totalJobs >= 7
                ? 'bg-red-100 text-red-700'
                : ($totalJobs >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600');
            $cdlLabels = collect(['Driver'])
                ->when($driver->cdl_a, fn($c) => $c->push('CDL A'))
                ->when($driver->cdl_b, fn($c) => $c->push('CDL B'));
        @endphp
        <div class="bg-white rounded-xl border shadow-sm flex flex-col"
             data-driver-card
             data-total-jobs="{{ $totalJobs }}"
             data-expanded="false">

            {{-- Card header --}}
            <div class="flex items-center gap-2 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-sm font-bold flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-semibold text-sm text-gray-900 leading-tight truncate">{{ $driver->full_name }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $wlClass }}">{{ $totalJobs }}</span>
                        @if ($driver->delivery_jobs->isNotEmpty())
                            <span class="px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-semibold">{{ $driver->delivery_jobs->count() }}D</span>
                        @endif
                        @if ($driver->return_jobs->isNotEmpty())
                            <span class="px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 text-[10px] font-semibold">{{ $driver->return_jobs->count() }}R</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-[10px] text-gray-400 leading-tight flex-1 min-w-0 truncate">{{ $cdlLabels->implode(' | ') }}</span>
                        <button type="button"
                            class="dispatch-card-update-btn shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                            Update
                        </button>
                        <button type="button"
                            class="dc-view-all-btn shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                            View All
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== SEPARATE VIEW: two columns ===== --}}
            <div class="driver-card-separate flex divide-x flex-1 text-xs">

                {{-- Deliveries --}}
                <div class="flex-1 p-4 min-w-0 flex flex-col gap-2">
                    <p class="font-semibold text-blue-700 flex items-center gap-1.5 text-xs uppercase tracking-wide shrink-0">
                        <x-heroicon-o-truck class="w-3.5 h-3.5 shrink-0" />
                        Deliveries
                        <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->delivery_jobs->count() }}</span>
                    </p>
                    <div class="dc-scroll-section space-y-4 overflow-y-auto" style="max-height: 13rem;">
                        @forelse ($driver->delivery_jobs as $job)
                        @php
                            $addr = $job->order?->shippingAddress;
                            $effectiveDeliveryDate = $job->dispatch_delivery_date ?? $job->delivery_date;
                            $isOverdue = $effectiveDeliveryDate && \Carbon\Carbon::parse($effectiveDeliveryDate)->lt(today());
                            $isEarly   = $job->dispatch_delivery_date && $job->delivery_date
                                         && \Carbon\Carbon::parse($job->dispatch_delivery_date)->lt(\Carbon\Carbon::parse($job->delivery_date));
                        @endphp
                        <div class="flex items-start gap-2">
                            {{-- Editable priority circle --}}
                            <button type="button"
                                class="dispatch-priority-badge shrink-0 w-8 h-8 rounded-full text-xs font-bold flex items-center justify-center leading-none mt-0.5 {{ $job->delivery_priority ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-400 hover:bg-blue-100 hover:text-blue-600' }}"
                                data-uid="{{ $job->unique_id }}"
                                data-type="delivery"
                                data-priority="{{ $job->delivery_priority ?? '' }}"
                                title="Set delivery priority">{{ $job->delivery_priority ?? '—' }}</button>
                            {{-- Job details (click to jump) --}}
                            <button type="button"
                                class="flex-1 text-left space-y-0.5 py-1 hover:bg-blue-50 rounded transition-colors dispatch-card-jump min-w-0"
                                data-order-number="{{ $job->order?->order_number }}">
                                <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1 flex-wrap">
                                    @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                                    <span class="text-blue-600 mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                                    @if($isEarly)<span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 shrink-0">EARLY</span>@endif
                                </p>
                                @if($showAll ?? false)
                                    <p class="text-[10px] text-blue-500 font-medium leading-snug">
                                        {{ $effectiveDeliveryDate ? \Carbon\Carbon::parse($effectiveDeliveryDate)->format('M j') : '—' }}
                                        @if($isEarly)<span class="text-gray-400 line-through ml-1">{{ \Carbon\Carbon::parse($job->delivery_date)->format('M j') }}</span>@endif
                                    </p>
                                @endif
                                <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                                <p class="text-gray-400 leading-snug">From: {{ $job->deliveryStore?->store_name ?? 'Custom' }}</p>
                                <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                            </button>
                        </div>
                        @empty
                            <p class="text-gray-300 italic">None scheduled</p>
                        @endforelse
                    </div>
                </div>

                {{-- Returns --}}
                <div class="flex-1 p-4 min-w-0 flex flex-col gap-2">
                    <p class="font-semibold text-purple-700 flex items-center gap-1.5 text-xs uppercase tracking-wide shrink-0">
                        <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5 shrink-0" />
                        Returns
                        <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->return_jobs->count() }}</span>
                    </p>
                    <div class="dc-scroll-section space-y-4 overflow-y-auto" style="max-height: 13rem;">
                        @forelse ($driver->return_jobs as $job)
                        @php
                            $addr = $job->order?->shippingAddress;
                            $effectivePickupDate = $job->dispatch_return_date ?? $job->pickup_date;
                            $isOverdue  = $effectivePickupDate && \Carbon\Carbon::parse($effectivePickupDate)->lt(today());
                            $isLatePickup = $job->dispatch_return_date && $job->pickup_date
                                            && \Carbon\Carbon::parse($job->dispatch_return_date)->gt(\Carbon\Carbon::parse($job->pickup_date));
                        @endphp
                        <div class="flex items-start gap-2">
                            {{-- Editable priority circle --}}
                            <button type="button"
                                class="dispatch-priority-badge shrink-0 w-8 h-8 rounded-full text-xs font-bold flex items-center justify-center leading-none mt-0.5 {{ $job->pickup_priority ? 'bg-purple-100 text-purple-700 hover:bg-purple-200' : 'bg-gray-100 text-gray-400 hover:bg-purple-100 hover:text-purple-600' }}"
                                data-uid="{{ $job->unique_id }}"
                                data-type="return"
                                data-priority="{{ $job->pickup_priority ?? '' }}"
                                title="Set return priority">{{ $job->pickup_priority ?? '—' }}</button>
                            {{-- Job details (click to jump) --}}
                            <button type="button"
                                class="flex-1 text-left space-y-0.5 py-1 hover:bg-purple-50 rounded transition-colors dispatch-card-jump min-w-0"
                                data-order-number="{{ $job->order?->order_number }}">
                                <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1 flex-wrap">
                                    @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                                    <span class="text-purple-600 mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                                    @if($isLatePickup)<span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 shrink-0">LATE PICKUP</span>@endif
                                </p>
                                @if($showAll ?? false)
                                    <p class="text-[10px] text-purple-500 font-medium leading-snug">
                                        {{ $effectivePickupDate ? \Carbon\Carbon::parse($effectivePickupDate)->format('M j') : '—' }}
                                        @if($isLatePickup)<span class="text-gray-400 line-through ml-1">{{ \Carbon\Carbon::parse($job->pickup_date)->format('M j') }}</span>@endif
                                    </p>
                                @endif
                                <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                                <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                                <p class="text-gray-400 leading-snug">To: {{ $job->pickupStore?->store_name ?? 'Custom' }}</p>
                            </button>
                        </div>
                        @empty
                            <p class="text-gray-300 italic">None scheduled</p>
                        @endforelse
                    </div>
                </div>

            </div>{{-- end separate --}}

            {{-- ===== COMBINED VIEW: single priority-sorted list ===== --}}
            <div class="driver-card-combined hidden p-4 flex flex-col gap-2 text-xs">
                <p class="font-semibold text-gray-600 flex items-center gap-1.5 text-xs uppercase tracking-wide shrink-0">
                    <x-heroicon-o-list-bullet class="w-3.5 h-3.5 shrink-0" />
                    Full Day Workflow
                    <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->combined_jobs->count() }} jobs</span>
                </p>
                <div class="dc-scroll-section space-y-3 overflow-y-auto" style="max-height: 13rem;">
                    @forelse ($driver->combined_jobs as $job)
                    @php
                        $isDelivery      = $job->getAttribute('_slot') === 'delivery';
                        $priority        = $isDelivery ? $job->delivery_priority : $job->pickup_priority;
                        $addr            = $job->order?->shippingAddress;
                        $effectiveDate   = $isDelivery
                            ? ($job->dispatch_delivery_date ?? $job->delivery_date)
                            : ($job->dispatch_return_date   ?? $job->pickup_date);
                        $rentalDate      = $isDelivery ? $job->delivery_date : $job->pickup_date;
                        $isOverdue       = $effectiveDate && \Carbon\Carbon::parse($effectiveDate)->lt(today());
                        $isEarly         = $isDelivery && $job->dispatch_delivery_date && $job->delivery_date
                                           && \Carbon\Carbon::parse($job->dispatch_delivery_date)->lt(\Carbon\Carbon::parse($job->delivery_date));
                        $isLatePickup    = !$isDelivery && $job->dispatch_return_date && $job->pickup_date
                                           && \Carbon\Carbon::parse($job->dispatch_return_date)->gt(\Carbon\Carbon::parse($job->pickup_date));
                    @endphp
                    <div class="flex items-start gap-2">
                        {{-- Editable priority badge --}}
                        <button type="button"
                            class="dispatch-priority-badge shrink-0 w-7 h-7 rounded-full text-[10px] font-bold flex items-center justify-center leading-none mt-0.5
                                {{ $priority
                                    ? ($isDelivery ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-purple-100 text-purple-700 hover:bg-purple-200')
                                    : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}"
                            data-uid="{{ $job->unique_id }}"
                            data-type="{{ $isDelivery ? 'delivery' : 'return' }}"
                            data-priority="{{ $priority ?? '' }}"
                            title="Set {{ $isDelivery ? 'delivery' : 'return' }} priority">{{ $priority ?? '—' }}</button>
                        {{-- Job detail (click to jump to table row) --}}
                        <button type="button"
                            class="flex-1 text-left space-y-1 border-l-2 {{ $isDelivery ? 'border-blue-300 hover:bg-blue-50' : 'border-purple-300 hover:bg-purple-50' }} pl-3 py-1 rounded-r transition-colors dispatch-card-jump"
                            data-order-number="{{ $job->order?->order_number }}"
                            title="Filter table to {{ $job->order?->order_number }}">
                            <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1 flex-wrap">
                                @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                                <span class="{{ $isDelivery ? 'text-blue-600' : 'text-purple-600' }} mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                                <span class="ml-1 text-[10px] font-normal {{ $isDelivery ? 'text-blue-400' : 'text-purple-400' }}">{{ $isDelivery ? '↑ Del' : '↓ Ret' }}</span>
                                @if($isEarly)<span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">EARLY</span>@endif
                                @if($isLatePickup)<span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">LATE PICKUP</span>@endif
                            </p>
                            @if($showAll ?? false)
                                <p class="text-[10px] {{ $isDelivery ? 'text-blue-500' : 'text-purple-500' }} font-medium leading-snug">
                                    {{ $effectiveDate ? \Carbon\Carbon::parse($effectiveDate)->format('M j') : '—' }}
                                    @if($isEarly || $isLatePickup)
                                        <span class="text-gray-400 line-through ml-1">{{ $rentalDate ? \Carbon\Carbon::parse($rentalDate)->format('M j') : '' }}</span>
                                    @endif
                                </p>
                            @endif
                            <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                            @if ($isDelivery)
                                <p class="text-gray-400 leading-snug">From: {{ $job->deliveryStore?->store_name ?? 'Custom' }}</p>
                            @else
                                <p class="text-gray-400 leading-snug">To: {{ $job->pickupStore?->store_name ?? 'Custom' }}</p>
                            @endif
                            <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                        </button>
                    </div>
                    @empty
                        <p class="text-gray-300 italic">No jobs scheduled</p>
                    @endforelse
                </div>
            </div>{{-- end combined --}}

        </div>
        @empty
            <div class="col-span-full text-sm text-gray-400 italic p-4">No drivers with active assignments.</div>
        @endforelse

    </div>{{-- end active grid --}}
</div>{{-- end active section wrapper --}}

{{-- Toggle logic handled by event delegation in index.blade.php --}}

@endif
