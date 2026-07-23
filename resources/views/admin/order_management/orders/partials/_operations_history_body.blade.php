@php
    /**
     * Operations History modal body (Enhancement 6/7) — read-only.
     * Inputs: $order, $tabs (staging|dispatch|checklist|assignment collections
     * of {at, title, detail, employee, actor, product, tone}).
     * Rendered on demand by OperationsHistoryController and injected into the
     * modal shell in edit.blade.php. Tab switching is wired there (delegated,
     * CSP-safe) via [data-ops-tab] / [data-ops-panel].
     */
    $tabMeta = [
        'staging'    => ['label' => 'Staging History',           'hint' => 'Machine staged, fuel & key verified, and how it left the yard'],
        'dispatch'   => ['label' => 'Driver Dispatch History',   'hint' => 'Ready to Go → Load Map & Go → Arrived, per leg'],
        'checklist'  => ['label' => 'Customer Checklist History', 'hint' => 'Delivery, return, and removal checklist activity'],
        'assignment' => ['label' => 'Equipment Assignment',      'hint' => 'Equipment reserved and every switch'],
    ];

    $toneClass = [
        'emerald' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'indigo'  => 'bg-indigo-50 text-indigo-800 border-indigo-200',
        'amber'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'sky'     => 'bg-sky-50 text-sky-800 border-sky-200',
        'gray'    => 'bg-gray-100 text-gray-700 border-gray-200',
    ];
@endphp

<div data-ops-history-body>
    {{-- Tab nav --}}
    <div class="flex flex-wrap gap-1 border-b border-gray-200 px-1" role="tablist">
        @foreach ($tabMeta as $key => $meta)
            <button type="button" data-ops-tab="{{ $key }}"
                class="ops-tab px-3 py-2 -mb-px text-sm font-semibold border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 {{ $loop->first ? 'border-sky-600 text-sky-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $meta['label'] }}
                <span class="ml-1 inline-flex items-center justify-center min-w-5 px-1.5 rounded-full text-xs font-bold {{ $tabs[$key]->count() ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-400' }}">{{ $tabs[$key]->count() }}</span>
            </button>
        @endforeach
    </div>

    {{-- Panels --}}
    @foreach ($tabMeta as $key => $meta)
        <div data-ops-panel="{{ $key }}" class="{{ $loop->first ? '' : 'hidden' }} px-1 py-3">
            <p class="text-xs text-gray-500 mb-3">{{ $meta['hint'] }}</p>

            @if ($tabs[$key]->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 py-8 text-center text-sm text-gray-500">
                    No {{ strtolower($meta['label']) }} recorded for this order yet.
                </div>
            @else
                <ol class="divide-y divide-gray-100">
                    @foreach ($tabs[$key] as $event)
                        <li class="py-2.5 flex items-start gap-3">
                            <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded border text-xs font-semibold {{ $toneClass[$event['tone']] ?? $toneClass['gray'] }}">
                                {{ $tabMeta[$key]['label'] === 'Equipment Assignment' ? 'Assignment' : \Illuminate\Support\Str::of($meta['label'])->before(' History') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $event['title'] }}
                                    @if (!empty($event['product']))
                                        <span class="ml-1 text-xs font-normal text-gray-400">· {{ $event['product'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($event['detail']))
                                    <div class="text-sm text-gray-600 break-words">{{ $event['detail'] }}</div>
                                @endif
                                <div class="mt-0.5 text-xs text-gray-400">
                                    {{ $event['at'] ? \Illuminate\Support\Carbon::parse($event['at'])->format('D M j, Y · g:i A') : 'time not recorded' }}
                                    @if (!empty($event['employee'])) · by {{ $event['employee'] }} @endif
                                    @if (!empty($event['actor'])) · logged in: {{ $event['actor'] }} @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endforeach
</div>
