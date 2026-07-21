{{-- One lifecycle segment of the Queue Line pipeline (2026-07-20 layout).
     Always rendered — an empty stage shows its placeholder so the board
     reads as the same three-stage pipeline no matter the day's volume.
     Inputs: $sectionKey, $meta, $sections, $ui, $fuelByAssignment. --}}
<section class="rounded-lg border-2 {{ $meta['wrapper'] }} overflow-hidden bg-white" aria-label="{{ $meta['label'] }}"
    data-queue-section="{{ $sectionKey }}">
    <h2 class="flex flex-wrap items-center gap-2 px-4 py-2 font-bold uppercase tracking-wide {{ $ui['sectionHeader'] }} {{ $meta['band'] }}">
        <span class="rounded bg-white/25 px-2 py-0.5 text-sm font-extrabold" aria-hidden="true">{{ $meta['stage'] }}</span>
        @if ($meta['icon'] === 'wrench')
            <x-heroicon-s-wrench-screwdriver class="w-6 h-6" />
        @elseif ($meta['icon'] === 'check')
            <x-heroicon-s-check-circle class="w-6 h-6" />
        @else
            <x-heroicon-s-check-badge class="w-6 h-6" />
        @endif
        {{ $meta['label'] }}
        <span class="ml-1 rounded-full bg-white/25 px-2.5 py-0.5 text-sm font-semibold">{{ count($sections[$sectionKey] ?? []) }}</span>
        <span class="ml-auto normal-case tracking-normal font-normal {{ $ui['body'] }} opacity-90">{{ $meta['hint'] }}</span>
    </h2>

    @if (count($sections[$sectionKey] ?? []) > 0)
        <div class="p-4 flex flex-wrap items-start gap-4">
            @foreach ($sections[$sectionKey] as $item)
                @include('livewire.queue-line.partials._card', [
                    'item' => $item,
                    'ui' => $ui,
                    'fuelByAssignment' => $fuelByAssignment,
                    'delivered' => $sectionKey === 'delivered',
                    'stagedReady' => $sectionKey === 'ready',
                ])
            @endforeach
        </div>
    @else
        <div class="p-10 text-center">
            <p class="{{ $ui['body'] }} text-gray-400">{{ $meta['empty'] }}</p>
        </div>
    @endif
</section>
