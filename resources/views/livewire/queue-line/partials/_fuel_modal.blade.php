@php
    $fuelUnit = $fuelItem->softAssignment?->equipment;
    $isReverse = $fuelMode === 'reverse';
@endphp
{{-- Queue Fuel Verification — the sign-off belongs to the PHYSICAL unit:
     the equipment id shown here is submitted with the confirmation, and the
     service rejects it if the assignment changed after this screen loaded. --}}
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="fuel-modal-{{ $fuelItem->id }}-{{ $fuelMode }}" data-fuel-modal="{{ $fuelMode }}">
    <div class="bg-white rounded-lg w-full max-w-xl shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $isReverse ? 'Reverse Fuel Verification' : 'Verify Fuel Full' }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Order #{{ $fuelItem->order->order_number }} — {{ $fuelItem->product_name }}
                </p>
            </div>
            <button type="button" wire:click="closeFuel"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4 flex flex-col gap-4">
            @if ($fuelError)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $fuelError }}
                </div>
            @endif

            {{-- The exact physical unit this action applies to --}}
            <div class="rounded-lg border {{ $isReverse ? 'border-amber-300 bg-amber-50' : 'border-emerald-300 bg-emerald-50' }} p-3">
                <p class="text-xs uppercase tracking-wide font-semibold {{ $isReverse ? 'text-amber-700' : 'text-emerald-700' }}">
                    {{ $isReverse ? 'Currently verified equipment' : 'Equipment being verified' }}
                </p>
                <p class="text-base font-bold text-gray-900 mt-0.5">
                    {{ $fuelUnit?->equipment_name ?? '—' }}
                    <span class="font-normal text-gray-500">· {{ $fuelUnit?->equipment_id }}</span>
                </p>
                @if ($isReverse && $fuelCurrent)
                    <p class="text-xs text-gray-600 mt-1">
                        Verified by {{ $fuelCurrent->performedBy?->first_name }} {{ $fuelCurrent->performedBy?->last_name }}
                        at {{ $fuelCurrent->created_at->format('M j · g:i A') }}
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="fuel-performed-by" class="text-sm font-medium text-gray-700 required">Performed By</label>
                    <select id="fuel-performed-by" wire:model="fuelPerformedBy"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                        <option value="">Select yourself…</option>
                        @foreach ($activeEmployees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($isReverse)
                    <div>
                        <label for="fuel-reason" class="text-sm font-medium text-gray-700 required">Reason</label>
                        <input type="text" id="fuel-reason" wire:model="fuelReason" maxlength="255"
                            placeholder="e.g. verified the wrong machine"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeFuel"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Cancel
                </button>
                @if ($isReverse)
                    <button type="button" wire:click="confirmFuelReverse({{ $fuelCurrent?->id ?? 0 }})"
                        wire:loading.attr="disabled" @disabled(! $fuelCurrent)
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                        Reverse Verification
                    </button>
                @else
                    <button type="button" wire:click="confirmFuelVerify({{ $fuelUnit?->id ?? 0 }})"
                        wire:loading.attr="disabled" @disabled(! $fuelUnit)
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                        Confirm Fuel Full
                    </button>
                @endif
            </div>

            {{-- Append-only history — the full trail, newest first --}}
            @if ($fuelHistory->isNotEmpty())
                <div class="border-t border-gray-200 pt-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Fuel History</h3>
                    <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 text-xs" data-fuel-history>
                        @foreach ($fuelHistory as $event)
                            <div class="py-1.5 flex items-start gap-2">
                                @if ($event->action === 'verified')
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">Verified</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 shrink-0">Reversed</span>
                                @endif
                                <span class="text-gray-600">
                                    {{ $event->equipment?->equipment_name }} ({{ $event->equipment?->equipment_id }})
                                    — {{ $event->performedBy?->first_name }} {{ $event->performedBy?->last_name }}
                                    <span class="text-gray-400">(actor: {{ $event->createdBy?->first_name }} {{ $event->createdBy?->last_name }})</span>
                                    · {{ $event->created_at->format('M j · g:i A') }}
                                    · {{ $event->source }}
                                    @if ($event->reason)
                                        · “{{ $event->reason }}”
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
