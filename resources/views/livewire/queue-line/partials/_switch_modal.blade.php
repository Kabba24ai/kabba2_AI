@php
    use App\Services\QueueLine\QueueLineEligibility;

    $currentUnit = $switchingItem->softAssignment?->equipment;
@endphp
{{-- Switch Equipment — canonical reassignment (EquipmentReassignmentService).
     Search accepts barcode/QR scanner input (scanners type the code); exact
     equipment-id matches sort first, then direct product matches. --}}
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="switch-modal-{{ $switchingItem->id }}" data-switch-modal>
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $currentUnit ? 'Switch Equipment' : 'Assign Equipment' }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Order #{{ $switchingItem->order->order_number }} —
                    ordered <span class="font-medium text-gray-700">{{ $switchingItem->product_name }}</span>
                    @if ($currentUnit)
                        · currently <span class="font-medium text-gray-700">{{ $currentUnit->equipment_name }}</span>
                    @endif
                </p>
            </div>
            <button type="button" wire:click="closeSwitch"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4 flex flex-col gap-4">
            @if ($switchError)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $switchError }}
                </div>
            @endif

            {{-- Who is physically doing this (shared terminals) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="switch-performed-by" class="text-sm font-medium text-gray-700 required">Performed By</label>
                    <select id="switch-performed-by" wire:model="switchPerformedBy"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                        <option value="">Select yourself…</option>
                        @foreach ($activeEmployees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="switch-reason" class="text-sm font-medium text-gray-700">
                        Reason <span class="text-gray-400">(required for non-direct equipment)</span>
                    </label>
                    <input type="text" id="switch-reason" wire:model="switchReason" maxlength="255"
                        placeholder="e.g. reserved unit buried in back row"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                </div>
            </div>

            {{-- Scan or search --}}
            <div>
                <label for="switch-search" class="text-sm font-medium text-gray-700">Scan barcode or search</label>
                <input type="text" id="switch-search" wire:model.live.debounce.300ms="switchSearch"
                    autofocus autocomplete="off"
                    placeholder="Scan a barcode, or type an equipment ID / name / brand"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-md"
                data-switch-candidates>
                @forelse ($switchCandidates as $candidate)
                    @php
                        $candidateClass = $candidate->assigned_product_id === null
                            ? QueueLineEligibility::ASSIGNMENT_UNKNOWN
                            : ((int) $candidate->assigned_product_id === (int) $switchingItem->product_id
                                ? QueueLineEligibility::ASSIGNMENT_DIRECT
                                : QueueLineEligibility::ASSIGNMENT_ALTERNATE);
                    @endphp
                    <div class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-gray-900 truncate">
                                {{ $candidate->equipment_name }}
                                <span class="font-normal text-gray-400">· {{ $candidate->equipment_id }}</span>
                            </div>
                            <div class="text-xs text-gray-500 truncate">
                                {{ $candidate->assignedProduct?->product_name ?? 'No product mapping' }}
                                @if ($candidate->status_label)
                                    · {{ $candidate->status_label }}
                                @endif
                            </div>
                        </div>
                        @if ($candidateClass === QueueLineEligibility::ASSIGNMENT_DIRECT)
                            <span class="px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 border border-green-200 shrink-0">Direct</span>
                        @elseif ($candidateClass === QueueLineEligibility::ASSIGNMENT_ALTERNATE)
                            <span class="px-2 py-0.5 rounded text-xs bg-purple-50 text-purple-700 border border-purple-200 shrink-0">Alternate</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-600 border border-slate-200 shrink-0">Product Unknown</span>
                        @endif
                        <button type="button" wire:click="confirmSwitch({{ $candidate->id }})"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-xs font-medium rounded bg-sky-600 text-white hover:bg-sky-700 disabled:opacity-50 shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                            Select
                        </button>
                    </div>
                @empty
                    <p class="px-3 py-6 text-sm text-gray-500 text-center">
                        No available equipment matches{{ trim($switchSearch) !== '' ? ' "' . $switchSearch . '"' : '' }}.
                        Rented units cannot be staged.
                    </p>
                @endforelse
            </div>

            <p class="text-xs text-gray-400">
                Scheduling conflicts never block a switch — anything this creates is flagged
                automatically on Schedule Conflicts for admin review.
            </p>
        </div>
    </div>
</div>
