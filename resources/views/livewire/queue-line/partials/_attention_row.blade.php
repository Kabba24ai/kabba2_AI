@php
    use App\Services\QueueLine\QueueLineEligibility;

    $bucket = QueueLineEligibility::bucketFor($item);
    $isRushed = $item->queueLineItem?->isRushed() ?? false;
    $orderEditUrl = route('admin.order-management.orders.edit', $groupOrder->unique_id);
    $bucketBadgeClass = match ($bucket) {
        'overdue' => 'bg-orange-500 text-white',
        'today' => 'bg-sky-600 text-white',
        default => 'bg-gray-200 text-gray-700',
    };
@endphp
{{-- Slim attention row: eligible, but no machine has been selected yet.
     No imagery, no stage control — a machine must be assigned first. --}}
<div class="w-full flex flex-wrap items-center gap-3 rounded-md border-2 border-dashed border-amber-400 bg-amber-50 px-4 py-3 cursor-pointer focus-within:ring-2 focus-within:ring-sky-500"
    onclick="window.location='{{ $orderEditUrl }}'"
    data-queue-row="unassigned" data-order-product-id="{{ $item->id }}">
    <x-heroicon-s-wrench-screwdriver class="w-5 h-5 text-amber-600" aria-hidden="true" />
    <span class="{{ $ui['badge'] }} font-bold uppercase tracking-wide text-amber-800">Needs Equipment Assignment</span>
    <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase {{ $bucketBadgeClass }}">{{ ucfirst($bucket) }}</span>
    @if ($isRushed)
        <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-red-600 text-white">RUSH</span>
    @endif
    <span class="{{ $ui['title'] }} text-gray-900 font-semibold truncate max-w-xs" title="{{ $item->product_name }}">{{ $item->product_name }}</span>
    <span class="{{ $ui['body'] }} text-gray-600">{{ QueueLineEligibility::deliveryTypeLabel($item) }}</span>
    <span class="{{ $ui['body'] }} text-gray-500">{{ $item->deliveryStore?->store_name ?? '—' }}</span>
    <span class="{{ $ui['body'] }} text-gray-500">
        {{ \Carbon\Carbon::parse($item->delivery_date)->format('D M j') }}{{ $item->delivery_time ? ' · ' . \Carbon\Carbon::parse($item->delivery_time)->format('g:i A') : '' }}
    </span>
    <span class="flex-1"></span>
    <span class="flex items-center gap-1.5" onclick="event.stopPropagation()">
        <button type="button" wire:click="openSwitch({{ $item->id }})" title="Select the machine for this order"
            class="{{ $ui['btnBase'] }} border-sky-700 bg-sky-600 text-white hover:bg-sky-700">Assign Equipment</button>
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
    </span>
</div>
