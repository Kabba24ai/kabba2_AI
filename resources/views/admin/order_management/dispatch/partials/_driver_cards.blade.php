@if ($driverCards->isNotEmpty())

{{-- Section header: toggle controls all cards --}}
<div class="flex items-center gap-3 mb-3">
    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Driver Workload</span>
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
</div>

{{-- Cards grid — data-card-mode drives which inner section is visible --}}
<div id="driver-cards-container" class="grid grid-cols-4 gap-4 mb-4" data-card-mode="separate">
    @foreach ($driverCards as $driver)
    @php
        $initials = strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1));
        $hasJobs  = $driver->delivery_jobs->isNotEmpty() || $driver->return_jobs->isNotEmpty();
    @endphp
    <div class="bg-white rounded-xl border shadow-sm flex flex-col {{ $hasJobs ? '' : 'opacity-50' }}">

        {{-- Card header --}}
        <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-sm font-bold flex items-center justify-center shrink-0">
                {{ $initials }}
            </div>
            <div class="flex flex-col min-w-0">
                <span class="font-semibold text-sm text-gray-900 leading-tight">{{ $driver->full_name }}</span>
                @php
                    $cdlLabels = collect(['Driver'])
                        ->when($driver->cdl_a, fn($c) => $c->push('CDL A'))
                        ->when($driver->cdl_b, fn($c) => $c->push('CDL B'));
                @endphp
                <span class="text-[10px] text-gray-400 leading-tight">{{ $cdlLabels->implode(' | ') }}</span>
            </div>
            <div class="ml-auto flex items-center gap-2 text-xs shrink-0">
                @if ($hasJobs)
                    <button type="button"
                        class="dispatch-card-update-btn px-2.5 py-1 rounded font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        Update
                    </button>
                @endif
                @if ($driver->delivery_jobs->isNotEmpty())
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold">{{ $driver->delivery_jobs->count() }}D</span>
                @endif
                @if ($driver->return_jobs->isNotEmpty())
                    <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-semibold">{{ $driver->return_jobs->count() }}R</span>
                @endif
                @if (!$hasJobs)
                    <span class="text-gray-400 italic">No jobs</span>
                @endif
            </div>
        </div>

        {{-- ===== SEPARATE VIEW: two columns ===== --}}
        <div class="driver-card-separate flex divide-x flex-1 text-xs">

            {{-- Deliveries --}}
            <div class="flex-1 p-4 space-y-4 min-w-0">
                <p class="font-semibold text-blue-700 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                    <x-heroicon-o-truck class="w-3.5 h-3.5 shrink-0" />
                    Deliveries
                    <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->delivery_jobs->count() }}</span>
                </p>
                @forelse ($driver->delivery_jobs as $job)
                @php
                    $addr = $job->order?->shippingAddress;
                    $isOverdue = $job->delivery_date && \Carbon\Carbon::parse($job->delivery_date)->lt(today());
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
                        <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1">
                            @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                            <span class="text-blue-600 mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                        </p>
                        <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                        <p class="text-gray-400 leading-snug">From: {{ $job->deliveryStore?->store_name ?? 'Custom' }}</p>
                        <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                    </button>
                </div>
                @empty
                    <p class="text-gray-300 italic">None scheduled</p>
                @endforelse
            </div>

            {{-- Returns --}}
            <div class="flex-1 p-4 space-y-4 min-w-0">
                <p class="font-semibold text-purple-700 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                    <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5 shrink-0" />
                    Returns
                    <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->return_jobs->count() }}</span>
                </p>
                @forelse ($driver->return_jobs as $job)
                @php
                    $addr = $job->order?->shippingAddress;
                    $isOverdue = $job->pickup_date && \Carbon\Carbon::parse($job->pickup_date)->lt(today());
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
                        <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1">
                            @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                            <span class="text-purple-600 mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                        </p>
                        <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                        <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                        <p class="text-gray-400 leading-snug">To: {{ $job->pickupStore?->store_name ?? 'Custom' }}</p>
                    </button>
                </div>
                @empty
                    <p class="text-gray-300 italic">None scheduled</p>
                @endforelse
            </div>

        </div>{{-- end separate --}}

        {{-- ===== COMBINED VIEW: single priority-sorted list ===== --}}
        <div class="driver-card-combined hidden p-4 space-y-3 text-xs">
            <p class="font-semibold text-gray-600 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                <x-heroicon-o-list-bullet class="w-3.5 h-3.5 shrink-0" />
                Full Day Workflow
                <span class="ml-auto text-gray-400 font-normal normal-case tracking-normal">{{ $driver->combined_jobs->count() }} jobs</span>
            </p>
            @forelse ($driver->combined_jobs as $job)
            @php
                $isDelivery = $job->getAttribute('_slot') === 'delivery';
                $priority   = $isDelivery ? $job->delivery_priority : $job->pickup_priority;
                $addr       = $job->order?->shippingAddress;
                $jobDate    = $isDelivery ? $job->delivery_date : $job->pickup_date;
                $isOverdue  = $jobDate && \Carbon\Carbon::parse($jobDate)->lt(today());
            @endphp
            <button type="button"
                class="w-full text-left space-y-1 border-l-2 {{ $isDelivery ? 'border-blue-300 hover:bg-blue-50' : 'border-purple-300 hover:bg-purple-50' }} pl-3 py-1 rounded-r transition-colors dispatch-card-jump"
                data-order-number="{{ $job->order?->order_number }}"
                title="Filter table to {{ $job->order?->order_number }}">
                <div class="flex items-start gap-2">
                    <span class="shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-full {{ $isDelivery ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-[10px] font-bold mt-0.5">
                        {{ $priority ?? '—' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 leading-tight flex items-center gap-1">
                            @if($isOverdue)<x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 shrink-0" />@endif
                            <span class="{{ $isDelivery ? 'text-blue-600' : 'text-purple-600' }} mr-1">#{{ ltrim($job->order?->order_number ?? '', '#') }}</span>{{ $job->order?->customer_name ?? '—' }}
                            <span class="ml-1 text-[10px] font-normal {{ $isDelivery ? 'text-blue-400' : 'text-purple-400' }}">{{ $isDelivery ? '↑ Del' : '↓ Ret' }}</span>
                        </p>
                        <p class="text-gray-600 leading-snug">{{ $job->equipment?->equipment_name ?? $job->softAssignment?->equipment?->equipment_name ?? $job->product_name }}</p>
                        @if ($isDelivery)
                            <p class="text-gray-400 leading-snug">From: {{ $job->deliveryStore?->store_name ?? 'Custom' }}</p>
                        @else
                            <p class="text-gray-400 leading-snug">To: {{ $job->pickupStore?->store_name ?? 'Custom' }}</p>
                        @endif
                        <p class="text-gray-500 leading-snug">{{ $addr?->address ?? '' }}{{ $addr?->address && $addr?->city ? ', ' : '' }}{{ $addr?->city ?? '—' }}</p>
                    </div>
                </div>
            </button>
            @empty
                <p class="text-gray-300 italic">No jobs scheduled</p>
            @endforelse
        </div>{{-- end combined --}}

    </div>
    @endforeach
</div>

{{-- Toggle logic handled by event delegation in index.blade.php --}}

@endif
