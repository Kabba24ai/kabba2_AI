@php
    $statusUnit = $statusItem->softAssignment?->equipment;
    $stagedAt = $statusItem->queueLineItem?->staged_at;
@endphp
{{-- Green thumbs-up — read-only staged status + Return to Pending.
     Everything shown is the CURRENT episode-bound record; history is
     append-only and never deleted. --}}
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="staged-status-modal-{{ $statusItem->id }}" data-staged-status-modal>
    <div class="bg-white rounded-lg w-full max-w-lg shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-heroicon-s-hand-thumb-up class="w-5 h-5 text-green-600" aria-hidden="true" />
                    Staged
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    ID: {{ $statusItem->order->order_number }} — {{ $statusItem->product_name }}
                </p>
            </div>
            <button type="button" wire:click="closeStagedStatus"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4 flex flex-col gap-3 text-sm">
            <div class="rounded-lg border border-green-200 bg-green-50 p-3">
                <p class="font-bold text-gray-900">
                    {{ $statusUnit?->equipment_name ?? '—' }}
                    <span class="font-normal text-gray-500">· #{{ $statusUnit?->equipment_id }}</span>
                </p>
                <p class="text-xs text-green-700 mt-0.5">Fueled, keyed, and staged — ready for handoff.</p>
            </div>

            <dl class="divide-y divide-gray-100">
                <div class="flex justify-between py-1.5">
                    <dt class="text-gray-500">Performed By</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $statusFuel?->performedBy?->first_name }} {{ $statusFuel?->performedBy?->last_name }}
                    </dd>
                </div>
                <div class="flex justify-between py-1.5">
                    <dt class="text-gray-500">Entered By</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $statusFuel?->createdBy?->first_name }} {{ $statusFuel?->createdBy?->last_name }}
                    </dd>
                </div>
                <div class="flex justify-between py-1.5">
                    <dt class="text-gray-500">Fuel</dt>
                    <dd>
                        @if ($statusFuel)
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-600 text-white">Full — Verified</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-400 text-amber-950">Not Verified</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-1.5">
                    <dt class="text-gray-500">Key</dt>
                    <dd>
                        @if ($statusKey)
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-600 text-white">With Machine</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-400 text-amber-950">Not Confirmed</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-1.5">
                    <dt class="text-gray-500">Staged</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $stagedAt?->format('D M j · g:i A') ?? '—' }}
                    </dd>
                </div>
            </dl>

            <div class="flex justify-between items-center gap-3 pt-2 border-t border-gray-100">
                <button type="button" wire:click="returnToPending({{ $statusItem->id }})"
                    wire:confirm="Return this item to Pending? Fuel and key will need to be confirmed again."
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-amber-800 bg-white border border-amber-300 rounded-lg hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                    Return to Pending
                </button>
                <button type="button" wire:click="closeStagedStatus"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
