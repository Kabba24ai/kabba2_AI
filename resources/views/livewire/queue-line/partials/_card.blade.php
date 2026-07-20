@php
    use App\Services\QueueLine\QueueLineEligibility;

    $equipment = $item->softAssignment?->equipment;
    $bucket = QueueLineEligibility::bucketFor($item);
    $queueItem = $item->queueLineItem;
    $isStaged = $queueItem?->isStaged() ?? false;
    $isRushed = $queueItem?->isRushed() ?? false;
    $orderEditUrl = route('admin.order-management.orders.edit', $groupOrder->unique_id);
    $optionsCount = QueueLineEligibility::optionsCount($item);
    $rrLabel = QueueLineEligibility::rentalReadyLabel($item);

    $rrTone = match ($rrLabel) {
        QueueLineEligibility::RR_READY => 'bg-green-100 text-green-800 border-green-200',
        QueueLineEligibility::RR_DAMAGED => 'bg-red-100 text-red-800 border-red-200',
        QueueLineEligibility::RR_DRAFT => 'bg-amber-100 text-amber-800 border-amber-200',
        default => 'bg-gray-100 text-gray-600 border-gray-200',
    };

    $equipStatus = $equipment?->current_status?->value;
    $equipStatusLabel = $equipment?->status_label;
    $equipTone = match ($equipStatus) {
        'maintenance' => 'bg-amber-100 text-amber-800 border-amber-200',
        'damaged' => 'bg-red-100 text-red-800 border-red-200',
        'rented' => 'bg-gray-100 text-gray-600 border-gray-200',
        default => 'bg-green-50 text-green-700 border-green-200',
    };

    $bucketBadgeClass = match ($bucket) {
        'overdue' => 'bg-orange-500 text-white',
        'today' => 'bg-sky-600 text-white',
        default => 'bg-gray-200 text-gray-700',
    };
    $bucketLabel = ucfirst($bucket);
@endphp
<div class="{{ $ui['cardW'] }} {{ $ui['cardH'] }} flex flex-col rounded-lg border-2 {{ $isRushed ? 'border-red-500' : 'border-gray-200' }} bg-white shadow-sm cursor-pointer overflow-hidden focus-within:ring-2 focus-within:ring-sky-500"
    onclick="window.location='{{ $orderEditUrl }}'"
    data-queue-row="card" data-order-product-id="{{ $item->id }}" data-assignment="{{ $assignment }}">

    {{-- Visual area — fixed height in every state --}}
    @if ($assignment === QueueLineEligibility::ASSIGNMENT_DIRECT)
        <div class="{{ $ui['imgH'] }} shrink-0 bg-gray-50 border-b border-gray-100 flex items-center justify-center overflow-hidden"
            data-assignment-visual="direct">
            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->product_name }}"
                class="max-h-full max-w-full object-contain" loading="lazy" />
        </div>
    @elseif ($assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
        {{-- The shared Alternate Equipment treatment: never the ordered
             product's photo — an approved substitution needing attention --}}
        <div class="{{ $ui['imgH'] }} shrink-0 bg-purple-700 flex flex-col items-center justify-center gap-1 text-white border-b border-purple-800"
            data-assignment-visual="alternate" role="img" aria-label="Alternate Equipment — the assigned machine differs from the ordered product">
            <x-heroicon-o-arrows-right-left class="w-10 h-10" aria-hidden="true" />
            <span class="font-bold uppercase tracking-widest {{ $ui['title'] }}">Alternate Equipment</span>
            <span class="{{ $ui['badge'] }} text-purple-100">Verify the machine before loading</span>
        </div>
    @else
        <div class="{{ $ui['imgH'] }} shrink-0 bg-slate-600 flex flex-col items-center justify-center gap-1 text-white border-b border-slate-700"
            data-assignment-visual="unknown" role="img" aria-label="Assignment Product Unknown — confirm the assigned machine">
            <x-heroicon-o-question-mark-circle class="w-10 h-10" aria-hidden="true" />
            <span class="font-bold uppercase tracking-widest {{ $ui['title'] }}">Assignment Product Unknown</span>
            <span class="{{ $ui['badge'] }} text-slate-200">Confirm this machine matches the order</span>
        </div>
    @endif

    <div class="flex flex-col flex-1 min-h-0 p-3">
        {{-- Urgency + queue state --}}
        <div class="flex items-center gap-2 mb-2">
            <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase {{ $bucketBadgeClass }}">{{ $bucketLabel }}</span>
            @if ($isRushed)
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-red-600 text-white">RUSH</span>
            @endif
            <span class="ml-auto px-2 py-0.5 rounded {{ $ui['badge'] }} font-semibold {{ $isStaged ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                {{ $isStaged ? 'On Queue Line' : 'Not Staged' }}
            </span>
        </div>

        {{-- Equipment identity first (visual hierarchy #1) --}}
        <div class="{{ $ui['title'] }} font-bold text-gray-900 truncate" title="{{ $equipment?->equipment_name }}">
            {{ $equipment?->equipment_name ?? '—' }}
        </div>
        <div class="{{ $ui['body'] }} text-gray-600 truncate" title="Ordered: {{ $item->product_name }}">
            Ordered: <span class="font-medium text-gray-800">{{ $item->product_name }}</span>
        </div>
        @if ($assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
            <div class="{{ $ui['body'] }} text-purple-700 font-medium truncate"
                title="Assigned product: {{ $equipment?->assignedProduct?->product_name }}">
                Assigned product: {{ $equipment?->assignedProduct?->product_name }}
            </div>
        @endif

        {{-- Rental Ready + equipment status + options: fixed row --}}
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            @if ($assignment === QueueLineEligibility::ASSIGNMENT_DIRECT)
                <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} bg-green-50 text-green-700 border-green-200">Direct Assignment</span>
            @endif
            <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} {{ $rrTone }}">RR: {{ $rrLabel }}</span>
            @if ($equipStatusLabel)
                <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} {{ $equipTone }}">{{ $equipStatusLabel }}</span>
            @endif
            <span class="ml-auto px-1.5 py-0.5 rounded {{ $ui['badge'] }} {{ $optionsCount > 0 ? 'bg-sky-100 text-sky-800 font-bold' : 'bg-gray-100 text-gray-500' }}">
                Options: {{ $optionsCount }}
            </span>
        </div>

        {{-- Fuel — bound to the CURRENT assignment episode: switching the
             equipment makes this fall back to Not Verified automatically --}}
        @php
            $fuelVerification = $item->softAssignment
                ? ($fuelByAssignment[$item->softAssignment->id] ?? null)
                : null;
        @endphp
        <div class="mt-1.5 flex items-center gap-1.5" data-fuel-state="{{ $fuelVerification ? 'verified' : 'not-verified' }}">
            @if ($fuelVerification)
                <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} bg-emerald-100 text-emerald-800 border-emerald-300 font-semibold">
                    Fuel Full — Verified
                </span>
                <span class="{{ $ui['badge'] }} text-gray-500 truncate"
                    title="Verified on {{ $equipment?->equipment_id }} by {{ $fuelVerification->performedBy?->first_name }} {{ $fuelVerification->performedBy?->last_name }} at {{ $fuelVerification->created_at->format('g:i A') }}">
                    {{ $fuelVerification->performedBy?->first_name }} {{ $fuelVerification->performedBy?->last_name }}
                    · {{ $fuelVerification->created_at->format('g:i A') }}
                </span>
            @else
                <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} bg-amber-50 text-amber-800 border-amber-300 font-semibold">
                    Fuel Not Verified
                </span>
            @endif
        </div>

        {{-- Logistics --}}
        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 {{ $ui['body'] }} text-gray-600">
            <span class="font-medium">{{ QueueLineEligibility::deliveryTypeLabel($item) }}</span>
            <span>{{ $item->deliveryStore?->store_name ?? '—' }}</span>
            <span>
                {{ \Carbon\Carbon::parse($item->delivery_date)->format('D M j') }}{{ $item->delivery_time ? ' · ' . \Carbon\Carbon::parse($item->delivery_time)->format('g:i A') : '' }}
            </span>
        </div>

        {{-- Actions — propagation stopped so buttons never trigger card navigation --}}
        <div class="mt-auto pt-2 flex flex-wrap gap-1.5" onclick="event.stopPropagation()">
            <button type="button" wire:click="openSwitch({{ $item->id }})" title="Stage a different machine for this order"
                class="{{ $ui['btnBase'] }} border-sky-300 text-sky-700 bg-white hover:bg-sky-50">Switch Equipment</button>
            @if ($fuelVerification)
                <button type="button" wire:click="openFuelReverse({{ $item->id }})" title="Reverse this fuel verification (reason required)"
                    class="{{ $ui['btnBase'] }} border-amber-300 text-amber-800 bg-white hover:bg-amber-50">Reverse Fuel</button>
            @else
                <button type="button" wire:click="openFuelVerify({{ $item->id }})" title="Confirm the assigned machine is full of fuel"
                    class="{{ $ui['btnBase'] }} border-emerald-700 bg-emerald-600 text-white hover:bg-emerald-700">Verify Fuel Full</button>
            @endif
            @if ($isStaged)
                <button type="button" wire:click="unstage({{ $item->id }})"
                    class="{{ $ui['btnBase'] }} border-gray-300 text-gray-700 bg-white hover:bg-gray-50">Not Staged</button>
            @else
                <button type="button" wire:click="stage({{ $item->id }})"
                    class="{{ $ui['btnBase'] }} border-green-700 bg-green-600 text-white hover:bg-green-700">On Queue Line</button>
            @endif
            @if ($isRushed)
                <button type="button" wire:click="unrush({{ $item->id }})"
                    class="{{ $ui['btnBase'] }} border-red-300 text-red-700 bg-white hover:bg-red-50">Clear RUSH</button>
            @else
                <button type="button" wire:click="rush({{ $item->id }})"
                    class="{{ $ui['btnBase'] }} border-gray-300 text-gray-700 bg-white hover:bg-gray-50">RUSH</button>
            @endif
            <button type="button" wire:click="openHistory({{ $item->id }})" title="Assignment, fuel, and release timeline for this item"
                class="{{ $ui['btnBase'] }} border-gray-300 text-gray-700 bg-white hover:bg-gray-50">History</button>
            <button type="button" wire:click="removeToday({{ $item->id }})" title="Hide from the Queue Line for today only"
                class="{{ $ui['btnBase'] }} border-gray-300 text-gray-700 bg-white hover:bg-gray-50">Remove Today</button>
            <button type="button" wire:confirm="Permanently remove this item from Queue Line management?"
                wire:click="removeForever({{ $item->id }})" title="Permanently remove from Queue Line management (restorable)"
                class="{{ $ui['btnBase'] }} border-gray-300 text-gray-500 bg-white hover:bg-gray-50">Remove Forever</button>
        </div>
    </div>
</div>
