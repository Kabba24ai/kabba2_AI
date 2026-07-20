@php
    use App\Services\QueueLine\QueueLineHistory;

    $historyUnit = $historyItem->softAssignment?->equipment;

    $typeMeta = [
        QueueLineHistory::TYPE_ASSIGNMENT => ['label' => 'Assignment', 'tone' => 'bg-sky-50 text-sky-700 border-sky-200'],
        QueueLineHistory::TYPE_FUEL => ['label' => 'Fuel', 'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        QueueLineHistory::TYPE_RELEASE => ['label' => 'Release', 'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
        QueueLineHistory::TYPE_REVERSAL => ['label' => 'Reversal', 'tone' => 'bg-amber-50 text-amber-800 border-amber-200'],
        QueueLineHistory::TYPE_ADMIN => ['label' => 'Admin', 'tone' => 'bg-gray-100 text-gray-600 border-gray-200'],
    ];
@endphp
{{-- Queue Line operational timeline (Phase 4 §5) — a read-only projection of
     the records the lifecycle already writes (order history, fuel ledger,
     sidecar, live assignment). Answers: which machine was selected, who
     verified its fuel, and how did the item leave the Queue Line? --}}
<div class="fixed inset-0 z-[9999] bg-gray-900/60 flex items-start justify-center overflow-y-auto p-4"
    wire:key="history-modal-{{ $historyItem->id }}" data-history-modal>
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-xl mt-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Queue Line History</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Order #{{ $historyItem->order->order_number }} — {{ $historyItem->product_name }}
                    @if ($historyUnit)
                        · currently {{ $historyUnit->equipment_name }} ({{ $historyUnit->equipment_id }})
                    @endif
                </p>
            </div>
            <button type="button" wire:click="closeHistory"
                class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="Close">&times;</button>
        </div>

        <div class="p-4">
            @if ($historyEvents->isEmpty())
                <p class="text-sm text-gray-500 py-6 text-center">No Queue Line activity has been recorded for this item yet.</p>
            @else
                <div class="max-h-[28rem] overflow-y-auto divide-y divide-gray-100" data-history-timeline>
                    @foreach ($historyEvents as $event)
                        @php $meta = $typeMeta[$event['type']] ?? $typeMeta[QueueLineHistory::TYPE_ADMIN]; @endphp
                        <div class="py-2.5 flex items-start gap-3 text-sm">
                            <span class="mt-0.5 px-2 py-0.5 rounded border text-xs font-semibold shrink-0 w-24 text-center {{ $meta['tone'] }}">
                                {{ $meta['label'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900">
                                    {{ $event['title'] }}
                                    @if ($event['equipment'])
                                        <span class="font-normal text-gray-500">· {{ $event['equipment'] }}</span>
                                    @endif
                                </p>
                                @if ($event['detail'])
                                    <p class="text-gray-600 mt-0.5">{{ $event['detail'] }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $event['at']?->format('D M j, Y · g:i A') }}
                                    @if ($event['employee'])
                                        · by {{ $event['employee'] }}
                                    @endif
                                    @if ($event['actor'] && $event['actor'] !== $event['employee'])
                                        · logged in: {{ $event['actor'] }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end pt-3">
                <button type="button" wire:click="closeHistory"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
