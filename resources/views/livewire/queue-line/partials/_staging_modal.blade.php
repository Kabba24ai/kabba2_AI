@php
    $stagingUnit = $stagingItem->softAssignment?->equipment;
    $canSubmit = $stagingPerformedBy !== '' && $stagingFuel === 'full' && $stagingKey === 'with_machine';
@endphp
{{-- Mark as Staged — one atomic readiness action (QueueLineStagingService):
     canonical fuel verification + key confirmation + staged latch. The
     equipment id shown is submitted with the confirmation, and the service
     rejects it if the assignment changed after this screen loaded. --}}
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="staging-modal-{{ $stagingItem->id }}" data-staging-modal>
    <div class="bg-white rounded-lg w-full max-w-xl shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Mark as Staged</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    ID: {{ $stagingItem->order->order_number }} — {{ $stagingItem->product_name }}
                </p>
            </div>
            <button type="button" wire:click="closeStaging"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4 flex flex-col gap-4">
            @if ($stagingError)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $stagingError }}
                </div>
            @endif

            {{-- The exact physical unit this staging applies to --}}
            <div class="rounded-lg border border-green-300 bg-green-50 p-3">
                <p class="text-xs uppercase tracking-wide font-semibold text-green-700">Equipment being staged</p>
                <p class="text-base font-bold text-gray-900 mt-0.5">
                    {{ $stagingUnit?->equipment_name ?? '—' }}
                    <span class="font-normal text-gray-500">· #{{ $stagingUnit?->equipment_id }}</span>
                </p>
            </div>

            {{-- Who physically staged it (shared terminals — self-selected) --}}
            <div>
                <label for="staging-performed-by" class="text-sm font-medium text-gray-700 required">Staged By</label>
                <select id="staging-performed-by" wire:model.live="stagingPerformedBy"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                    <option value="">Select the employee who staged this machine…</option>
                    @foreach ($activeEmployees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    Entered by {{ auth()->user()->full_name }} — the staging is attributed to the employee selected above.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Fuel --}}
                <fieldset class="rounded-lg border border-gray-200 p-3">
                    <legend class="text-sm font-medium text-gray-700 px-1 required">Fuel</legend>
                    <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                        <input type="radio" wire:model.live="stagingFuel" value="full" class="w-4 h-4" />
                        Full
                    </label>
                    <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                        <input type="radio" wire:model.live="stagingFuel" value="not_full" class="w-4 h-4" />
                        Not Full
                    </label>
                </fieldset>

                {{-- Key --}}
                <fieldset class="rounded-lg border border-gray-200 p-3">
                    <legend class="text-sm font-medium text-gray-700 px-1 required">Key</legend>
                    <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                        <input type="radio" wire:model.live="stagingKey" value="with_machine" class="w-4 h-4" />
                        With Machine
                    </label>
                    <label class="flex items-center gap-2 py-1 text-sm text-gray-800">
                        <input type="radio" wire:model.live="stagingKey" value="missing" class="w-4 h-4" />
                        Missing
                    </label>
                </fieldset>
            </div>

            @if ($stagingFuel === 'not_full' || $stagingKey === 'missing')
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    Staging records COMPLETE readiness — resolve the fuel or key issue first, then mark the machine as staged.
                </p>
            @endif

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeStaging"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Cancel
                </button>
                <button type="button" wire:click="confirmStaging({{ $stagingUnit?->id ?? 0 }})"
                    wire:loading.attr="disabled" @disabled(! $canSubmit || ! $stagingUnit)
                    data-staging-submit
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500">
                    Mark as Staged
                </button>
            </div>
        </div>
    </div>
</div>
