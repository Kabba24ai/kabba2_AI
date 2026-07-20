@php
    // Presentation scale per mode — every class literal so Tailwind compiles
    // them. Same component, same data, same actions in both modes.
    // Wall-board tiers (Phase 4 §6): 1080p baseline, wall2k (QHD) bumps type,
    // wall4k scales cards + type together — bigger cards, not more tiny
    // columns. Real responsive classes, never CSS transforms/zoom.
    // UI Reset (2026-07-20): the board is an INFORMATION DISPLAY — cards
    // carry no workflow buttons, so heights shrink to the seven content
    // rows. The only interactive card element is the ⋮ Queue menu.
    $ui = $wallboard
        ? [
            'cardW' => 'w-96 wall2k:w-[26rem] wall4k:w-[32rem]',
            'cardH' => 'h-[29rem] wall2k:h-[31rem] wall4k:h-[43rem]',
            'imgH' => 'h-44 wall2k:h-48 wall4k:h-72',
            'title' => 'text-xl wall2k:text-2xl wall4k:text-3xl',
            'body' => 'text-sm wall2k:text-base wall4k:text-xl',
            'badge' => 'text-sm wall2k:text-base wall4k:text-lg',
            'sectionHeader' => 'text-2xl wall2k:text-3xl wall4k:text-4xl',
            'groupHeader' => 'text-lg wall2k:text-xl wall4k:text-2xl',
            'btn' => 'px-3 py-2 text-sm wall2k:text-base wall4k:px-5 wall4k:py-3 wall4k:text-lg',
        ]
        : [
            'cardW' => 'w-80', 'cardH' => 'h-[25rem]', 'imgH' => 'h-36',
            'title' => 'text-base', 'body' => 'text-xs', 'badge' => 'text-xs',
            'sectionHeader' => 'text-lg', 'groupHeader' => 'text-base',
            'btn' => 'px-2 py-2 text-xs',
        ];
    $ui['btnBase'] = 'rounded border font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 ' . $ui['btn'];
@endphp
<div wire:poll.{{ $this->pollSeconds() }}s>
    {{-- Toolbar. The wall board stays a PASSIVE display: it keeps only its
         original store select and never renders the interactive filter bar. --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        @if ($wallboard)
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-2xl font-bold text-gray-900">Queue Line</span>
                <span class="text-lg text-gray-500" data-operational-date>{{ now()->format('l, F j, Y') }}</span>
                <label for="queue-store-filter" class="{{ $ui['body'] }} font-medium text-gray-700">Store</label>
                <select id="queue-store-filter" wire:model.live="store"
                    class="border border-gray-300 rounded-md px-3 py-2 {{ $ui['body'] }} text-gray-700 bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    <option value="all">All</option>
                    @foreach ($stores as $storeOption)
                        <option value="{{ $storeOption->id }}">{{ $storeOption->store_name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            @php
                $segBase = 'px-3 py-1.5 text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500';
                $segOn = 'bg-sky-600 text-white';
                $segOff = 'bg-white text-gray-600 hover:bg-gray-50';
                $selectBase = 'border border-gray-300 rounded-md px-3 py-1.5 text-xs text-gray-700 bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500';
            @endphp
            {{-- Filter Expansion (2026-07-20): Time · Store · Method ·
                 Category · Product — AND-combined; every count below derives
                 from the same filtered set. --}}
            <div class="flex flex-wrap items-center gap-3" data-queue-filter-bar>
                {{-- 1. Time — All (everything eligible: overdue + today +
                     tomorrow) | Today Only (overdue + today) --}}
                <div class="inline-flex rounded-md border border-gray-300 overflow-hidden divide-x divide-gray-300" role="group" aria-label="Time">
                    <button type="button" wire:click="$set('time', 'all')" class="{{ $segBase }} {{ $time !== 'today' ? $segOn : $segOff }}">All</button>
                    <button type="button" wire:click="$set('time', 'today')" class="{{ $segBase }} {{ $time === 'today' ? $segOn : $segOff }}">Today Only</button>
                </div>

                {{-- 2. Store — sticky via the app-wide FilterFreezer hook on
                     the page shell (order's delivery store, same as Schedule) --}}
                <select id="queue-store-filter" wire:model.live="store" aria-label="Store" class="{{ $selectBase }}">
                    <option value="all">All Stores</option>
                    @foreach ($stores as $storeOption)
                        <option value="{{ $storeOption->id }}">{{ $storeOption->store_name }}</option>
                    @endforeach
                </select>

                {{-- 3. Delivery method — canonical delivery_transport_mode --}}
                <div class="inline-flex rounded-md border border-gray-300 overflow-hidden divide-x divide-gray-300" role="group" aria-label="Delivery Method">
                    <button type="button" wire:click="$set('method', 'all')" class="{{ $segBase }} {{ $method === 'all' ? $segOn : $segOff }}">All</button>
                    <button type="button" wire:click="$set('method', 'Truck')" class="{{ $segBase }} {{ $method === 'Truck' ? $segOn : $segOff }}">Truck</button>
                    <button type="button" wire:click="$set('method', 'Store')" class="{{ $segBase }} {{ $method === 'Store' ? $segOn : $segOff }}">In-Store</button>
                </div>

                {{-- 4 + 5. Dependent Category → Product (ordered product) --}}
                <select id="queue-category-filter" wire:model.live="category" aria-label="Category" class="{{ $selectBase }}">
                    <option value="">All Categories</option>
                    @foreach ($categoryOptions as $categoryId => $categoryTitle)
                        <option value="{{ $categoryId }}">{{ $categoryTitle }}</option>
                    @endforeach
                </select>
                <select id="queue-product-filter" wire:model.live="product" aria-label="Product" class="{{ $selectBase }} max-w-56">
                    <option value="">All Products</option>
                    @foreach ($productOptions as $productId => $productName)
                        <option value="{{ $productId }}">{{ $productName }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            <span class="{{ $ui['body'] }} text-gray-400" data-last-updated>
                Updated {{ $lastUpdated->format('g:i:s A') }}
            </span>
            @if ($wallboard)
                <a href="{{ route('admin.order-management.queue-line.index') }}"
                    class="{{ $ui['body'] }} text-sky-700 hover:underline font-medium">Standard View</a>
            @else
                <a href="{{ route('admin.order-management.queue-line.wallboard') }}"
                    class="{{ $ui['body'] }} text-sky-700 hover:underline font-medium">Wall Board View</a>
                <button type="button" wire:click="$toggle('showSuppressed')"
                    class="{{ $ui['body'] }} text-gray-600 hover:text-gray-900 underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    {{ $showSuppressed ? 'Hide' : 'Show' }} Removed Forever ({{ $suppressedItems->count() }})
                </button>
            @endif
        </div>
    </div>

    @if ($wallboard && ! $boardError)
        @include('livewire.queue-line.partials._summary_strip', ['summary' => $summary, 'ui' => $ui])
    @endif

    @if ($actionError)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 {{ $ui['body'] }} text-red-800" role="alert">
            {{ $actionError }}
        </div>
    @endif

    @if ($actionNotice)
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 {{ $ui['body'] }} text-green-800" role="status">
            {{ $actionNotice }}
        </div>
    @endif

    @if ($switchingItem)
        @include('livewire.queue-line.partials._switch_modal', [
            'switchingItem' => $switchingItem,
            'switchCandidates' => $switchCandidates,
            'activeEmployees' => $activeEmployees,
        ])
    @endif

    @if ($fuelItem)
        @include('livewire.queue-line.partials._fuel_modal', [
            'fuelItem' => $fuelItem,
            'fuelCurrent' => $fuelCurrent,
            'fuelHistory' => $fuelHistory,
            'activeEmployees' => $activeEmployees,
        ])
    @endif

    @if ($historyItem)
        @include('livewire.queue-line.partials._history_modal', [
            'historyItem' => $historyItem,
            'historyEvents' => $historyEvents,
        ])
    @endif

    @if ($boardError)
        <div class="rounded-lg border border-red-200 bg-red-50 p-8 text-center" role="alert">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-500 mx-auto mb-2" />
            <p class="text-red-800 font-medium">{{ $boardError }}</p>
        </div>
    @else
        {{-- Removed Forever drawer (standard view) --}}
        @if (! $wallboard && $showSuppressed)
            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Removed Forever</h3>
                @forelse ($suppressedItems as $suppressedItem)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-200 last:border-0 text-sm">
                        <div class="text-gray-700">
                            <a href="{{ route('admin.order-management.orders.edit', $suppressedItem->order->unique_id) }}"
                                class="font-medium text-sky-700 hover:underline">Order #{{ $suppressedItem->order->order_number }}</a>
                            — {{ $suppressedItem->order->customer_name }}
                            — {{ $suppressedItem->orderProduct->product_name }}
                        </div>
                        <button type="button" wire:click="restoreItem({{ $suppressedItem->order_product_id }})"
                            class="{{ $ui['btnBase'] }} border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                            Restore
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No items are permanently removed.</p>
                @endforelse
            </div>
        @endif

        {{-- UI Iteration 1 — workflow sections: what still needs a technician,
             what is fully staged, what already left today. Urgency is a
             badge on each card, not a board section. --}}
        @php
            $sectionMeta = [
                'pending' => [
                    'label' => 'Queue Line — Pending', 'icon' => 'wrench',
                    'hint' => 'Still needs a machine pulled or fuel verified',
                    'band' => 'bg-sky-700 text-white', 'wrapper' => 'border-sky-200', 'bandExtra' => '',
                ],
                // Section key 'ready' is internal only — the user-visible label
                // is "Staged" (UI Iteration 1.1): Queue Line owns STAGING, not
                // operational readiness (Rental Ready) or release (Dispatch).
                'ready' => [
                    'label' => 'Queue Line — Staged', 'icon' => 'check',
                    'hint' => 'Machine confirmed · fuel verified — Queue Line work complete',
                    'band' => 'bg-green-600 text-white', 'wrapper' => 'border-green-200', 'bandExtra' => '',
                ],
                'delivered' => [
                    'label' => 'Delivered Today', 'icon' => 'truck',
                    'hint' => 'Already off the yard — reference only',
                    'band' => 'bg-gray-200 text-gray-700', 'wrapper' => 'border-gray-200', 'bandExtra' => '',
                ],
            ];
            $activeEmpty = count($sections['pending'] ?? []) === 0 && count($sections['ready'] ?? []) === 0;
        @endphp

        @if ($activeEmpty)
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-12 text-center">
                <x-heroicon-o-queue-list class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-600 font-medium">The Queue Line is clear.</p>
                <p class="text-sm text-gray-400 mt-1">No outbound rentals are due through tomorrow{{ $store !== 'all' ? ' for this store' : '' }}.</p>
            </div>
        @endif

        @foreach ($sectionMeta as $sectionKey => $meta)
            @if (count($sections[$sectionKey] ?? []) > 0)
                <section class="mb-8 rounded-lg border-2 {{ $meta['wrapper'] }} overflow-hidden" aria-label="{{ $meta['label'] }}"
                    data-queue-section="{{ $sectionKey }}">
                    <h2 class="flex flex-wrap items-center gap-2 px-4 py-2 font-bold uppercase tracking-wide {{ $ui['sectionHeader'] }} {{ $meta['band'] }} {{ $meta['bandExtra'] }}">
                        @if ($meta['icon'] === 'wrench')
                            <x-heroicon-s-wrench-screwdriver class="w-6 h-6" />
                        @elseif ($meta['icon'] === 'check')
                            <x-heroicon-s-check-circle class="w-6 h-6" />
                        @else
                            <x-heroicon-s-truck class="w-6 h-6" />
                        @endif
                        {{ $meta['label'] }}
                        <span class="ml-1 rounded-full bg-white/25 px-2.5 py-0.5 text-sm font-semibold">{{ count($sections[$sectionKey]) }}</span>
                        <span class="ml-auto normal-case tracking-normal font-normal {{ $ui['body'] }} opacity-90">{{ $meta['hint'] }}</span>
                    </h2>

                    <div class="p-4 flex flex-wrap items-start gap-4">
                        @foreach ($sections[$sectionKey] as $item)
                            @include('livewire.queue-line.partials._card', [
                                'item' => $item,
                                'ui' => $ui,
                                'fuelByAssignment' => $fuelByAssignment,
                                'delivered' => $sectionKey === 'delivered',
                            ])
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    @endif
</div>
