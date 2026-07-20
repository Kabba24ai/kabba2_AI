@php
    use App\Services\QueueLine\QueueLineEligibility;

    /**
     * UI Reset (2026-07-20) — Queue Line is a VISUAL YARD PULL BOARD, an
     * information display, not an order-management form. One canonical card
     * in every state (rows: identity / payment / product image / ordered
     * product / assigned equipment / equipment status + live warnings /
     * delivery info). The ONLY actions are true Queue Line management
     * (RUSH, Remove Today, Remove Forever) tucked into the ⋮ Queue menu.
     * Assignment, fuel verification, and history all still exist — they
     * simply live in their own workflows, not on this board.
     *
     * The image is ALWAYS a product image (the ordered product, except a
     * substitution shows the ASSIGNED product — the machine class actually
     * leaving the yard). Equipment owns no imagery here.
     *
     * Inputs: $item (OrderProduct), $ui (scale map), $delivered (bool).
     */
    $delivered = $delivered ?? false;

    $equipment = $delivered
        ? ($item->equipment ?? $item->softAssignment?->equipment)
        : $item->softAssignment?->equipment;

    if ($delivered) {
        // Display-only mirror of classifyAssignment for completed rows —
        // the canonical service is soft-assignment-scoped.
        $assignment = match (true) {
            $equipment === null => QueueLineEligibility::ASSIGNMENT_UNASSIGNED,
            $equipment->assigned_product_id === null => QueueLineEligibility::ASSIGNMENT_UNKNOWN,
            (int) $equipment->assigned_product_id === (int) $item->product_id => QueueLineEligibility::ASSIGNMENT_DIRECT,
            default => QueueLineEligibility::ASSIGNMENT_ALTERNATE,
        };
    } else {
        $assignment = QueueLineEligibility::classifyAssignment($item);
    }

    $order = $item->order;
    $queueItem = $item->queueLineItem;
    $isRushed = $queueItem?->isRushed() ?? false;
    $orderEditUrl = route('admin.order-management.orders.edit', $order->unique_id);
    $optionsCount = QueueLineEligibility::optionsCount($item);
    $rrLabel = QueueLineEligibility::rentalReadyLabel($item);

    $imageProduct = $assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE
        ? $equipment?->assignedProduct
        : $item->product;

    // Row 6 — equipment status + live warnings only (no reserved space)
    $equipStatus = $equipment?->current_status?->value;
    $statusChip = $equipment ? match ($equipStatus) {
        'maintenance' => ['label' => 'Maintenance Hold', 'tone' => 'bg-amber-100 text-amber-900 border-amber-300'],
        'damaged' => ['label' => 'Damaged', 'tone' => 'bg-red-100 text-red-900 border-red-300'],
        'rented' => ['label' => 'Rented', 'tone' => 'bg-gray-100 text-gray-600 border-gray-200'],
        default => ['label' => 'Available', 'tone' => 'bg-green-50 text-green-700 border-green-200'],
    } : null;

    $warnings = [];
    if ($equipment && $equipStatus !== 'damaged' && $rrLabel === QueueLineEligibility::RR_DAMAGED) {
        $warnings[] = ['label' => 'Damaged', 'tone' => 'bg-red-100 text-red-900 border-red-300'];
    }
    if ($equipment && $rrLabel !== QueueLineEligibility::RR_READY && $rrLabel !== QueueLineEligibility::RR_DAMAGED) {
        $warnings[] = ['label' => 'RR: ' . $rrLabel, 'tone' => 'bg-amber-50 text-amber-800 border-amber-200'];
    }
    if ($equipment?->store_id && $item->delivery_store_id && (int) $equipment->store_id !== (int) $item->delivery_store_id) {
        $warnings[] = [
            'label' => 'Wrong Location' . ($equipment->store?->store_name ? ' — at ' . $equipment->store->store_name : ''),
            'tone' => 'bg-orange-100 text-orange-900 border-orange-300',
        ];
    }
@endphp
<div class="{{ $ui['cardW'] }} {{ $ui['cardH'] }} relative flex flex-col rounded-lg border-2 {{ $isRushed && ! $delivered ? 'border-red-500' : 'border-gray-200' }} bg-white shadow-sm cursor-pointer focus-within:ring-2 focus-within:ring-sky-500 {{ $delivered ? 'opacity-90' : '' }}"
    onclick="window.location='{{ $orderEditUrl }}'"
    data-queue-row="card" data-order-product-id="{{ $item->id }}" data-assignment="{{ $assignment }}"
    @if ($delivered) data-delivered="1" @endif>

    <div class="shrink-0 px-3 pt-2.5 pb-2">
        {{-- Row 1 — identity: ID + customer --}}
        <div class="flex items-center gap-2">
            <a href="{{ $orderEditUrl }}" onclick="event.stopPropagation()"
                class="{{ $ui['groupHeader'] }} font-bold text-sky-700 hover:underline shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                ID: {{ $order->order_number }}
            </a>
            @if ($isRushed && ! $delivered)
                <span class="px-1.5 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-red-600 text-white shrink-0">RUSH</span>
            @endif
            <span class="ml-auto {{ $ui['body'] }} font-semibold text-gray-900 truncate text-right" title="{{ $order->customer_name }}">
                {{ $order->customer_name }}
            </span>

            @if (! $delivered)
                {{-- ⋮ Queue — the ONLY Queue Line management actions --}}
                <div class="relative shrink-0" onclick="event.stopPropagation()" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" data-manage-menu
                        class="flex items-center justify-center w-8 h-8 -mr-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                        title="Queue" aria-haspopup="true" :aria-expanded="open">
                        <x-heroicon-s-ellipsis-vertical class="w-5 h-5" aria-hidden="true" />
                    </button>
                    <div x-show="open" x-cloak @click="open = false"
                        class="absolute right-0 top-9 z-30 w-44 rounded-lg border border-gray-200 bg-white shadow-lg py-1 {{ $ui['body'] }}">
                        @if ($isRushed)
                            <button type="button" wire:click="unrush({{ $item->id }})" class="w-full text-left px-3 py-2 text-red-700 hover:bg-red-50">Remove RUSH</button>
                        @else
                            <button type="button" wire:click="rush({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Mark as RUSH</button>
                        @endif
                        <button type="button" wire:click="removeToday({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Remove Today</button>
                        <button type="button" wire:confirm="Permanently remove this item from Queue Line management?"
                            wire:click="removeForever({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-500 hover:bg-gray-50">Remove Forever</button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Row 2 — payment status only --}}
        <div class="mt-1.5 flex items-center gap-1.5">
            @if ($order->last_payment_status === null)
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-medium bg-gray-100 text-gray-600">No Payment Recorded</span>
            @else
                {!! \App\Helpers\CustomHelper::paymentStatusBadge($order->last_payment_status) !!}
            @endif
            @if ($delivered)
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-green-600 text-white">Delivered</span>
            @endif
        </div>
    </div>

    {{-- Row 3 — product image (always a PRODUCT image) --}}
    <div class="{{ $ui['imgH'] }} relative shrink-0 bg-gray-50 border-y border-gray-100 flex items-center justify-center"
        data-assignment-visual="{{ $assignment }}">
        <img src="{{ $imageProduct?->image_url }}" alt="{{ $imageProduct?->product_name ?? $item->product_name }}"
            class="max-h-full max-w-full object-contain" loading="lazy" />

        @if ($assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
            <div class="absolute inset-x-0 top-0 bg-purple-700 text-white text-center py-1 {{ $ui['badge'] }} font-bold uppercase tracking-widest shadow"
                role="note" aria-label="Substitute — the assigned machine intentionally differs from the ordered product">
                Substitute
            </div>
        @elseif ($assignment === QueueLineEligibility::ASSIGNMENT_UNKNOWN)
            <div class="absolute inset-x-0 top-0 bg-slate-600 text-white text-center py-1 {{ $ui['badge'] }} font-bold uppercase tracking-widest shadow"
                role="note" aria-label="Confirm the assigned machine matches the order">
                Confirm Match
            </div>
        @elseif ($assignment === QueueLineEligibility::ASSIGNMENT_UNASSIGNED)
            <div class="absolute inset-x-0 top-0 bg-amber-500 text-white text-center py-1 {{ $ui['badge'] }} font-bold uppercase tracking-widest shadow"
                role="note" aria-label="Needs Equipment Assignment">
                Needs Equipment Assignment
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 min-h-0 px-3 pt-2 pb-3">
        {{-- Row 4 — ordered product --}}
        <div class="{{ $ui['title'] }} font-bold text-gray-900 truncate" title="Ordered: {{ $item->product_name }}">
            {{ $item->product_name }}
        </div>
        @if ($assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
            <div class="{{ $ui['body'] }} text-purple-700 font-semibold truncate"
                title="Substituting with {{ $equipment?->assignedProduct?->product_name }}">
                Substituting with {{ $equipment?->assignedProduct?->product_name }}
            </div>
        @endif
        @if ($optionsCount > 0)
            <div class="{{ $ui['badge'] }} text-sky-800 font-medium">+ {{ $optionsCount }} {{ $optionsCount === 1 ? 'option' : 'options' }} on this order</div>
        @endif

        {{-- Row 5 — assigned equipment --}}
        <div class="mt-1.5" data-pull-line>
            @if ($equipment)
                <div class="{{ $ui['body'] }} font-semibold text-gray-900 truncate" title="{{ $equipment->equipment_name }}">
                    {{ $equipment->equipment_name }}
                </div>
                <div class="{{ $ui['badge'] }} text-gray-500">#{{ $equipment->equipment_id }}</div>
            @else
                <div class="{{ $ui['body'] }} italic text-amber-700 font-medium">No equipment selected</div>
            @endif
        </div>

        {{-- Row 6 — equipment status + live warnings (no space reserved when idle) --}}
        @if ($statusChip)
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5" data-warnings>
                <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} font-semibold {{ $statusChip['tone'] }}">{{ $statusChip['label'] }}</span>
                @foreach ($warnings as $warning)
                    <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} font-semibold {{ $warning['tone'] }}">{{ $warning['label'] }}</span>
                @endforeach
            </div>
        @endif

        {{-- Row 7 — delivery info (Schedule page conventions: same icons, same labels) --}}
        <div class="mt-auto pt-2 flex flex-wrap items-center gap-x-2 gap-y-1 {{ $ui['body'] }} text-gray-600" data-delivery-line>
            @php $iconColor = $delivered ? 'text-green-600' : 'text-yellow-600'; @endphp
            @if ($item->delivery_transport_mode === 'Truck')
                <x-heroicon-o-truck class="w-4 h-4 shrink-0 {{ $iconColor }}" aria-hidden="true" />
            @else
                <x-heroicon-o-building-storefront class="w-4 h-4 shrink-0 {{ $iconColor }}" aria-hidden="true" />
            @endif
            <span class="font-medium">{{ QueueLineEligibility::deliveryTypeLabel($item) }}</span>
            <span>{{ $item->deliveryStore?->store_name ?? '—' }}</span>
            <span>
                {{ \Carbon\Carbon::parse($item->delivery_date)->format('D M j') }}{{ $item->delivery_time ? ' · ' . \Carbon\Carbon::parse($item->delivery_time)->format('g:i A') : '' }}
            </span>
            @if ($delivered && $queueItem?->completed_at)
                <span class="px-1.5 py-0.5 rounded {{ $ui['badge'] }} font-semibold bg-green-100 text-green-800">
                    Delivered {{ $queueItem->completed_at->format('g:i A') }}
                </span>
            @endif
        </div>
    </div>
</div>
