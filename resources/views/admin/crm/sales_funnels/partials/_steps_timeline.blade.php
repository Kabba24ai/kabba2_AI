{{-- Tailwind/Laravel Blade static design (converted from your JSX) --}}
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-base font-semibold text-gray-900">Funnel Timeline</h4>

            <div class="flex items-center gap-2 mt-1 flex-wrap">
                {{-- Trigger badge --}}
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-md text-xs font-medium bg-blue-50 text-blue-700 border-blue-200">
                    {{-- Zap icon --}}
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <span>Event: {{ str($funnel->trigger_event)->replace('_', ' ')->title() }}</span>
                </div>

                <span class="text-sm text-gray-400">•</span>

                <span class="text-xs text-gray-600">
                    Starts
                    @if ((int) $funnel->date_value === 0 && (int) $funnel->hour_value === 0 && (int) $funnel->minute_value === 0)
                        at event
                    @else
                        {{ $funnel->date_value ? $funnel->date_value . ' ' . Str::plural('day', $funnel->date_value) : '' }}
                        {{ $funnel->hour_value ? $funnel->hour_value . ' ' . Str::plural('hour', $funnel->hour_value) : '' }}
                        {{ $funnel->minute_value ? $funnel->minute_value . ' ' . Str::plural('minute', $funnel->minute_value) : '' }}
                        {{ str($funnel->trigger_event_timing)->replace('Event', '')->trim() }}
                    @endif
                </span>

                <span class="text-sm text-gray-400">•</span>

                <p class="text-sm text-gray-600">{{ $funnel->steps_count }} {{ $funnel->steps_count == 1 ? 'Step' : 'Steps' }}</p>
            </div>
        </div>

        {{-- Your existing button (keep your data attrs) --}}
        <button type="button"
            class="flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
            data-open-add-step data-funnel-id="{{ $funnel->id }}" data-funnel-unique-id="{{ $funnel->unique_id }}">
            {{-- Plus icon --}}
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Add Step
        </button>
    </div>

    {{-- Timeline --}}
    <div class="relative pl-8">
        {{-- vertical line --}}
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-400 via-blue-300 to-blue-200"></div>

        @foreach ($funnel->steps as $index => $step)
            <div class="relative mb-6">
                {{-- START badge --}}
                @if ($index == 0)
                    <div class="absolute -left-8 -top-2 flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold shadow-md z-10">
                        <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        START
                    </div>
                @endif

                {{-- step number --}}
                <div class="absolute left-[-2rem] top-6 w-6 h-6 rounded-full bg-blue-500 border-4 border-white shadow-md flex items-center justify-center z-10">
                    <span class="text-white text-xs font-bold">{{ $index + 1 }}</span>
                </div>

                {{-- card --}}
                <div class="ml-6 bg-white border-2 border-gray-200 hover:border-blue-300 rounded-xl shadow-sm hover:shadow-md transition-all select-none">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-start gap-3 flex-1">
                                {{-- drag handle --}}
                                <div class="mt-1 text-gray-400 cursor-grab active:cursor-grabbing">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"
                                            stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                {{-- type icon --}}
                                <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600 shadow-md">
                                    {{-- MessageSquare --}}
                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"
                                            stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">
                                            {{ $step->step_type }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        {{-- Delay --}}
                                        <div class="flex items-center gap-2 text-sm">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100">
                                                <svg class="w-4 h-4 text-orange-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-xs text-gray-500">Delay</div>
                                                <div class="font-semibold text-gray-900">
                                                    @if ($step->delay_value == 0)
                                                        Immediate
                                                    @else
                                                        {{ $step->delay_value }} {{ ucfirst($step->delay_unit) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Total time --}}
                                        <div class="flex items-center gap-2 text-sm">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-purple-100">
                                                <svg class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M7 3v2m10-2v2M3 9h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M6 5h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z"
                                                        stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-xs text-gray-500">Total Time</div>
                                                <div class="font-semibold text-gray-900">
                                                    @if ($step->delay_value == 0)
                                                        At Start
                                                    @else
                                                        +{{ $step->delay_value }}{{ strtolower(substr($step->delay_unit, 0, 1)) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- actions --}}
                            <div class="flex items-center gap-1 flex-shrink-0">
                                {{-- Edit button using Heroicons (pencil-square) --}}
                                <button type="button"
                                    data-step-unique-id="{{ $step->unique_id }}"
                                    data-step-json='@json($step)'
                                    class="step-edit-button p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <x-heroicon-o-pencil class="w-5 h-5" />
                                </button>

                                <button type="button"
                                    data-step-unique-id="{{ $step->unique_id }}"
                                    class="step-delete-button p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        {{-- message preview --}}
                        <div class="pt-3 border-t border-gray-100">
                            <div class="mb-3">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="text-xs font-semibold text-gray-700">Message:</span>
                                    <span class="text-xs text-gray-600">
                                        {{ $step?->smsMessage->name ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="text-xs text-gray-700 bg-gray-50 p-2 rounded border border-gray-200 line-clamp-2">
                                    {{ $step->message }}
                                </div>
                            </div>

                            <p class="text-xs text-gray-500">
                                @if ($step->delay_value == 0)
                                    Sends when after funnel starts
                                @else
                                    Sends {{ $step->delay_value }} {{ ucfirst($step->delay_unit) }} after funnel starts
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if ($index == 0)
                    {{-- next step --}}
                    <div class="ml-6 pl-6 py-4 flex items-center gap-2 text-gray-400">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-xs font-medium">Next step</span>
                    </div>
                @endif
            </div>
        @endforeach



        {{-- Funnel complete --}}
        <div class="ml-6 pl-6 mt-4 flex items-center gap-2 px-4 py-3 bg-green-50 border-2 border-green-200 rounded-lg">
            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                <span class="text-white text-lg">✓</span>
            </div>
            <span class="text-sm font-semibold text-green-700">Funnel Complete</span>
        </div>
    </div>
</div>
