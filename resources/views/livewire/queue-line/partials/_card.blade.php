@php
    use App\Services\QueueLine\QueueLineEligibility;

    /**
     * UI Iteration 1 — the ONE Queue Line card. Every item renders this
     * partial in every state (direct / substitute / unknown / needs
     * equipment / delivered); only the equipment section adapts, never the
     * card shape. Yard-pull-board rule: the image is ALWAYS a product image
     * (the machine class physically leaving the yard) — equipment owns no
     * imagery here.
     *
     * Inputs: $item (OrderProduct), $ui (scale map), $fuelByAssignment,
     *         $delivered (bool — read-only Delivered Today card).
     */
    $delivered = $delivered ?? false;

    // Delivered rows show the unit that actually LEFT (the hard assignment
    // written at release); active rows always read the live soft assignment.
    $equipment = $delivered
        ? ($item->equipment ?? $item->softAssignment?->equipment)
        : $item->softAssignment?->equipment;

    if ($delivered) {
        // Display-only mirror of classifyAssignment for completed rows —
        // the canonical service is soft-assignment-scoped and completed
        // items have left the eligibility domain.
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
    $isStaged = $queueItem?->isStaged() ?? false;
    $isRushed = $queueItem?->isRushed() ?? false;
    $bucket = QueueLineEligibility::bucketFor($item);
    $orderEditUrl = route('admin.order-management.orders.edit', $order->unique_id);
    $optionsCount = QueueLineEligibility::optionsCount($item);
    $rrLabel = QueueLineEligibility::rentalReadyLabel($item);

    // The image ALWAYS comes from a product: the ordered product, except a
    // substitution shows the ASSIGNED product (what physically leaves).
    $imageProduct = $assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE
        ? $equipment?->assignedProduct
        : $item->product;

    // Fuel is episode-bound; delivered/unassigned cards have no live episode.
    $fuelVerification = (! $delivered && $item->softAssignment)
        ? ($fuelByAssignment[$item->softAssignment->id] ?? null)
        : null;
    $fuelState = $delivered ? 'closed' : ($assignment === QueueLineEligibility::ASSIGNMENT_UNASSIGNED
        ? 'unavailable'
        : ($fuelVerification ? 'verified' : 'not-verified'));

    // Warning badges — rendered ONLY while the condition is live.
    $equipStatus = $equipment?->current_status?->value;
    $warnings = [];
    if ($equipStatus === 'maintenance') {
        $warnings[] = ['label' => 'Maintenance Hold', 'tone' => 'bg-amber-100 text-amber-900 border-amber-300'];
    }
    if ($equipStatus === 'damaged' || $rrLabel === QueueLineEligibility::RR_DAMAGED) {
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

    $bucketBadgeClass = match ($bucket) {
        'overdue' => 'bg-orange-500 text-white',
        'today' => 'bg-sky-600 text-white',
        default => 'bg-gray-200 text-gray-700',
    };

    $completedViaLabel = match ($queueItem?->completed_via) {
        \App\Services\QueueLine\QueueLineService::VIA_DISPATCH_STARTED => 'Dispatch',
        \App\Services\QueueLine\QueueLineService::VIA_CUSTOMER_CHECKLIST_COMPLETED => 'Customer Handoff',
        default => null,
    };
@endphp
<div class="{{ $ui['cardW'] }} {{ $ui['cardH'] }} relative flex flex-col rounded-lg border-2 {{ $isRushed && ! $delivered ? 'border-red-500' : 'border-gray-200' }} bg-white shadow-sm cursor-pointer focus-within:ring-2 focus-within:ring-sky-500 {{ $delivered ? 'opacity-90' : '' }}"
    onclick="window.location='{{ $orderEditUrl }}'"
    data-queue-row="card" data-order-product-id="{{ $item->id }}" data-assignment="{{ $assignment }}"
    @if ($delivered) data-delivered="1" @endif>

    {{-- ── Header: Order ID · Customer · Payment (unchanged hierarchy) ── --}}
    <div class="shrink-0 px-3 pt-2.5 pb-2 border-b border-gray-100">
        <div class="flex items-start gap-2">
            <div class="min-w-0 flex-1">
                <a href="{{ $orderEditUrl }}" onclick="event.stopPropagation()"
                    class="{{ $ui['groupHeader'] }} font-bold text-sky-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Order #{{ $order->order_number }}
                </a>
                <div class="{{ $ui['body'] }} font-semibold text-gray-900 truncate" title="{{ $order->customer_name }}">
                    {{ $order->customer_name }}
                </div>
            </div>

            {{-- Manage menu — administrative actions live here, one tap away
                 but never competing with the technician buttons below --}}
            <div class="relative shrink-0" onclick="event.stopPropagation()" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" data-manage-menu
                    class="flex items-center justify-center w-9 h-9 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                    title="Manage this item" aria-haspopup="true" :aria-expanded="open">
                    <x-heroicon-s-ellipsis-vertical class="w-6 h-6" aria-hidden="true" />
                </button>
                <div x-show="open" x-cloak @click="open = false"
                    class="absolute right-0 top-10 z-30 w-52 rounded-lg border border-gray-200 bg-white shadow-lg py-1 {{ $ui['body'] }}">
                    @if (! $delivered)
                        @if ($equipment)
                            @if ($isStaged)
                                <button type="button" wire:click="unstage({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Take Off Queue Line</button>
                            @else
                                <button type="button" wire:click="stage({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Put On Queue Line</button>
                            @endif
                        @endif
                        @if ($isRushed)
                            <button type="button" wire:click="unrush({{ $item->id }})" class="w-full text-left px-3 py-2 text-red-700 hover:bg-red-50">Clear RUSH</button>
                        @else
                            <button type="button" wire:click="rush({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Mark RUSH</button>
                        @endif
                        @if ($fuelVerification)
                            <button type="button" wire:click="openFuelReverse({{ $item->id }})" class="w-full text-left px-3 py-2 text-amber-800 hover:bg-amber-50">Reverse Fuel Verification</button>
                        @endif
                    @endif
                    <button type="button" wire:click="openHistory({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">History</button>
                    @if (! $delivered)
                        <div class="my-1 border-t border-gray-100"></div>
                        <button type="button" wire:click="removeToday({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50">Remove Today</button>
                        <button type="button" wire:confirm="Permanently remove this item from Queue Line management?"
                            wire:click="removeForever({{ $item->id }})" class="w-full text-left px-3 py-2 text-gray-500 hover:bg-gray-50">Remove Forever</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
            @if ($order->last_payment_status === null)
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-medium bg-gray-100 text-gray-600">No Payment Recorded</span>
            @else
                {!! \App\Helpers\CustomHelper::paymentStatusBadge($order->last_payment_status) !!}
            @endif
            @if ($delivered)
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-green-600 text-white">Delivered</span>
            @else
                @if ($isRushed)
                    <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase bg-red-600 text-white">RUSH</span>
                @endif
                <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-bold uppercase {{ $bucketBadgeClass }}">{{ ucfirst($bucket) }}</span>
                @if ($isStaged)
                    <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-semibold bg-green-100 text-green-800 border border-green-200">On Queue Line</span>
                @endif
            @endif
        </div>
    </div>

    {{-- ── Product image — the visual identity of the card. Always a
         PRODUCT image: the ordered product, except a substitution shows the
         assigned product (the machine class actually leaving the yard). ── --}}
    <div class="{{ $ui['imgH'] }} relative shrink-0 bg-gray-50 border-b border-gray-100 flex items-center justify-center"
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
        {{-- Ordered product → assigned equipment → equipment ID (§3 order) --}}
        <div class="{{ $ui['title'] }} font-bold text-gray-900 truncate" title="Ordered: {{ $item->product_name }}">
            {{ $item->product_name }}
        </div>
        @if ($assignment === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
            <div class="{{ $ui['body'] }} text-purple-700 font-semibold truncate"
                title="Substituting with {{ $equipment?->assignedProduct?->product_name }}">
                Substituting with {{ $equipment?->assignedProduct?->product_name }}
            </div>
        @endif
        <div class="{{ $ui['body'] }} text-gray-700 truncate" data-pull-line
            title="{{ $equipment ? 'Pull: ' . $equipment->equipment_name . ' · ' . $equipment->equipment_id : 'No machine selected yet' }}">
            @if ($equipment)
                Pull: <span class="font-semibold text-gray-900">{{ $equipment->equipment_name }}</span>
                <span class="text-gray-500">· #{{ $equipment->equipment_id }}</span>
            @else
                <span class="italic text-amber-700 font-medium">No machine selected yet</span>
            @endif
        </div>
        @if ($optionsCount > 0)
            <div class="{{ $ui['badge'] }} text-sky-800 font-medium mt-0.5">+ {{ $optionsCount }} {{ $optionsCount === 1 ? 'option' : 'options' }} on this order</div>
        @endif

        {{-- Warnings — only conditions that are live right now; no space is
             reserved for inactive warnings --}}
        @if ($warnings !== [])
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5" data-warnings>
                @foreach ($warnings as $warning)
                    <span class="px-1.5 py-0.5 rounded border {{ $ui['badge'] }} font-semibold {{ $warning['tone'] }}">{{ $warning['label'] }}</span>
                @endforeach
            </div>
        @endif

        {{-- Fuel — bound to the CURRENT assignment episode --}}
        <div class="mt-2 flex items-center gap-1.5" data-fuel-state="{{ $fuelState }}">
            @if ($delivered)
                <span class="px-2 py-1 rounded {{ $ui['badge'] }} font-bold bg-green-600 text-white">
                    Delivered {{ $queueItem?->completed_at?->format('g:i A') }}{{ $completedViaLabel ? ' · ' . $completedViaLabel : '' }}
                </span>
            @elseif ($fuelState === 'verified')
                <span class="px-2 py-1 rounded {{ $ui['badge'] }} font-bold bg-emerald-600 text-white">Fuel Full ✓</span>
                <span class="{{ $ui['badge'] }} text-gray-500 truncate"
                    title="Verified on {{ $equipment?->equipment_id }} by {{ $fuelVerification->performedBy?->first_name }} {{ $fuelVerification->performedBy?->last_name }} at {{ $fuelVerification->created_at->format('g:i A') }}">
                    {{ $fuelVerification->performedBy?->first_name }} {{ $fuelVerification->performedBy?->last_name }}
                    · {{ $fuelVerification->created_at->format('g:i A') }}
                </span>
            @elseif ($fuelState === 'not-verified')
                <span class="px-2 py-1 rounded {{ $ui['badge'] }} font-bold bg-amber-400 text-amber-950">Fuel Not Verified</span>
            @else
                <span class="px-2 py-1 rounded {{ $ui['badge'] }} font-medium bg-gray-100 text-gray-500">Fuel — assign a machine first</span>
            @endif
        </div>

        {{-- Logistics --}}
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 {{ $ui['body'] }} text-gray-600">
            <span class="font-medium">{{ QueueLineEligibility::deliveryTypeLabel($item) }}</span>
            <span>{{ $item->deliveryStore?->store_name ?? '—' }}</span>
            <span>
                {{ \Carbon\Carbon::parse($item->delivery_date)->format('D M j') }}{{ $item->delivery_time ? ' · ' . \Carbon\Carbon::parse($item->delivery_time)->format('g:i A') : '' }}
            </span>
        </div>

        {{-- ── Primary technician actions: the yard tech's ONLY two jobs ── --}}
        <div class="mt-auto pt-2.5 grid grid-cols-2 gap-2" onclick="event.stopPropagation()">
            @if ($delivered)
                <div class="col-span-2 flex items-center justify-center rounded-md border border-green-200 bg-green-50 text-green-800 font-semibold {{ $ui['btn'] }}">
                    Off the yard — no action needed
                </div>
            @else
                @if ($assignment === QueueLineEligibility::ASSIGNMENT_UNASSIGNED)
                    <button type="button" wire:click="openSwitch({{ $item->id }})" title="Select the machine for this order"
                        class="{{ $ui['btnPrimary'] }} border-sky-700 bg-sky-600 text-white hover:bg-sky-700">
                        Assign Equipment
                    </button>
                    <button type="button" disabled title="Assign a machine before verifying fuel"
                        class="{{ $ui['btnPrimary'] }} border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed">
                        Verify Fuel Full
                    </button>
                @else
                    <button type="button" wire:click="openSwitch({{ $item->id }})" title="Confirm the staged machine, or select a different one"
                        class="{{ $ui['btnPrimary'] }} border-sky-600 bg-white text-sky-700 hover:bg-sky-50">
                        Confirm / Update Equipment
                    </button>
                    @if ($fuelVerification)
                        <div class="flex items-center justify-center rounded-md border border-emerald-300 bg-emerald-50 text-emerald-800 font-bold {{ $ui['btn'] }}" data-fuel-done>
                            Fuel Verified ✓
                        </div>
                    @else
                        <button type="button" wire:click="openFuelVerify({{ $item->id }})" title="Confirm the assigned machine is full of fuel"
                            class="{{ $ui['btnPrimary'] }} border-emerald-700 bg-emerald-600 text-white hover:bg-emerald-700">
                            Verify Fuel Full
                        </button>
                    @endif
                @endif
            @endif
        </div>
    </div>
</div>
