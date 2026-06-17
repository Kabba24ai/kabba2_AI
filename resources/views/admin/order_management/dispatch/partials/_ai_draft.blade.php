@if ($latestDraft && $latestDraft->assignments->isNotEmpty())
<div id="ai-draft-panel" class="mb-4">
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 py-3 bg-indigo-100 border-b border-indigo-200">
            <x-heroicon-o-cpu-chip class="w-4 h-4 text-indigo-700" />
            <span class="font-semibold text-sm text-indigo-800">AI Dispatch Draft</span>
            <span class="text-xs text-indigo-500">
                Generated {{ $latestDraft->created_at->diffForHumans() }}
                • {{ $latestDraft->assignments->count() }} recommendations
                @if ($latestDraft->confidence_score)
                    • {{ number_format($latestDraft->confidence_score, 0) }}% confidence
                @endif
            </span>
            <button type="button" id="toggle-ai-draft"
                class="ml-auto text-xs text-indigo-600 hover:text-indigo-800 font-medium underline">
                Hide
            </button>
        </div>

        {{-- AI Reasoning --}}
        @if ($latestDraft->ai_reasoning)
            <div id="ai-draft-body" class="px-5 py-3 border-b border-indigo-100 bg-white/60">
                <p class="text-xs text-gray-600 leading-relaxed">
                    <span class="font-semibold text-gray-700">AI Summary:</span> {{ $latestDraft->ai_reasoning }}
                </p>
            </div>
        @endif

        {{-- Assignment Cards --}}
        <div id="ai-draft-assignments" class="divide-y divide-indigo-100">
            @foreach ($latestDraft->assignments->sortBy(fn($a) => [$a->slot, $a->suggested_delivery_date ?? '9999-12-31']) as $assignment)
                @php
                    $op     = $assignment->orderProduct;
                    $driver = $assignment->recommendedDriver;
                    $isEarly= $assignment->is_early_delivery;
                    $isDelivery = $assignment->slot === 'delivery';
                    $slotColor  = $isDelivery ? 'text-blue-700 bg-blue-50' : 'text-purple-700 bg-purple-50';
                    $wasApplied = $assignment->was_applied;
                @endphp
                <div class="flex flex-wrap items-center gap-3 px-5 py-2.5 bg-white/40 hover:bg-white/70 text-xs">

                    {{-- Slot badge --}}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold text-xs {{ $slotColor }}">
                        @if ($isDelivery)
                            <x-heroicon-o-truck class="w-3 h-3" /> Delivery
                        @else
                            <x-heroicon-o-arrow-uturn-left class="w-3 h-3" /> Return
                        @endif
                    </span>

                    {{-- Order info --}}
                    <span class="font-medium text-gray-800">
                        {{ $op?->product_name ?? '—' }}
                    </span>
                    <span class="text-gray-400">{{ $op?->order?->order_number }}</span>
                    <span class="text-gray-500">{{ $op?->order?->customer_name }}</span>

                    {{-- Date --}}
                    @if ($assignment->suggested_delivery_date)
                        <span class="text-gray-500">
                            {{ \App\Helpers\CustomHelper::formatDate($assignment->suggested_delivery_date, 'M d, y') }}
                        </span>
                    @endif

                    {{-- Early delivery badge --}}
                    @if ($isEarly)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                            <x-heroicon-o-clock class="w-3 h-3" /> Early Delivery
                        </span>
                    @endif

                    {{-- Recommended driver --}}
                    <span class="ml-auto flex items-center gap-1 text-gray-700">
                        <x-heroicon-o-user class="w-3 h-3 text-gray-400" />
                        {{ $driver?->full_name ?? 'No driver recommended' }}
                    </span>

                    {{-- Applied / AI reasoning --}}
                    @if ($wasApplied)
                        <span class="text-green-600 font-medium">Applied</span>
                    @elseif ($assignment->ai_reasoning)
                        <span class="text-indigo-400 italic max-w-xs truncate" title="{{ $assignment->ai_reasoning }}">
                            {{ Str::limit($assignment->ai_reasoning, 60) }}
                        </span>
                    @endif

                </div>
            @endforeach
        </div>

    </div>
</div>

<script>
(function () {
    const toggle = document.getElementById('toggle-ai-draft');
    const body   = document.getElementById('ai-draft-assignments');
    const reason = document.getElementById('ai-draft-body');
    let hidden = false;

    toggle?.addEventListener('click', function () {
        hidden = !hidden;
        if (body) body.classList.toggle('hidden', hidden);
        if (reason) reason.classList.toggle('hidden', hidden);
        toggle.textContent = hidden ? 'Show' : 'Hide';
    });
})();
</script>
@endif
