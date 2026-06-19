@php
    $typeColors = [
        'suitability'        => 'blue',
        'limitation'         => 'red',
        'substitution'       => 'purple',
        'scheduling'         => 'orange',
        'safety'             => 'rose',
        'delivery'           => 'amber',
        'productivity'       => 'green',
        'terrain'            => 'teal',
        'customer_preference'=> 'indigo',
        'application_use_case' => 'cyan',
    ];
    $tc = $typeColors[$rule->rule_type->value] ?? 'gray';

    $sourceColors = [
        'admin'              => 'gray',
        'ai_generated'       => 'purple',
        'manufacturer'       => 'blue',
        'dealer'             => 'teal',
        'internal_experience'=> 'green',
    ];
    $sc = $sourceColors[$rule->source_type->value] ?? 'gray';
@endphp

<div class="rounded-xl border {{ $rule->approved_by_admin ? 'border-gray-100 bg-white' : 'border-amber-200 bg-amber-50' }} shadow-sm dark:border-gray-700 dark:bg-gray-900 p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap">

        {{-- Left: Rule info --}}
        <div class="flex-1 min-w-0">

            {{-- Badges row --}}
            <div class="flex items-center flex-wrap gap-2 mb-2">

                {{-- Rule type --}}
                <span class="inline-flex items-center rounded-full bg-{{ $tc }}-100 px-2.5 py-0.5 text-xs font-semibold text-{{ $tc }}-800 dark:bg-{{ $tc }}-900/40 dark:text-{{ $tc }}-300">
                    {{ $rule->rule_type->label() }}
                </span>

                {{-- Source --}}
                <span class="inline-flex items-center gap-1 rounded-full bg-{{ $sc }}-100 px-2.5 py-0.5 text-xs font-medium text-{{ $sc }}-700 dark:bg-{{ $sc }}-900/30 dark:text-{{ $sc }}-400">
                    @if($rule->source_type->value === 'ai_generated')
                        <x-heroicon-o-sparkles class="h-3 w-3" />
                    @endif
                    {{ $rule->source_type->label() }}
                </span>

                {{-- Approval status --}}
                @if($rule->approved_by_admin)
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        <x-heroicon-o-check-circle class="h-3 w-3" />
                        Approved
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <x-heroicon-o-clock class="h-3 w-3" />
                        Pending Review
                    </span>
                @endif

                {{-- Profile scope --}}
                @if($rule->profile)
                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-0.5 text-xs text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ $rule->profile->make }} {{ $rule->profile->model }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-0.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                        Category-wide
                    </span>
                @endif

                {{-- Priority --}}
                <span class="text-xs text-gray-400 dark:text-gray-500">P{{ $rule->priority }}</span>

                {{-- Confidence --}}
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rule->confidencePercent() }}% confidence</span>
            </div>

            {{-- Rule name --}}
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">{{ $rule->rule_name }}</h3>

            {{-- Condition / Recommendation / Reason --}}
            <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                <div><span class="font-semibold text-gray-700 dark:text-gray-300">Condition:</span> {{ $rule->condition }}</div>
                <div><span class="font-semibold text-gray-700 dark:text-gray-300">Recommendation:</span> {{ $rule->recommendation }}</div>
                <div><span class="font-semibold text-gray-700 dark:text-gray-300">Reason:</span> {{ $rule->reason }}</div>
            </div>

            {{-- Tags --}}
            @if(!empty($rule->tags))
                <div class="flex flex-wrap gap-1.5 mt-3">
                    @foreach($rule->tags as $tag)
                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Meta --}}
            <div class="mt-3 flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                <span>Created {{ $rule->created_at->diffForHumans() }}
                    @if($rule->creator) by {{ $rule->creator->first_name }} @endif
                </span>
                @if($rule->approved_by_admin && $rule->approver)
                    <span>· Approved by {{ $rule->approver->first_name }}</span>
                @endif
                @if($rule->last_reviewed_at)
                    <span>· Last reviewed {{ $rule->last_reviewed_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-2 shrink-0">

            {{-- Approve (if pending) --}}
            @if(!$rule->approved_by_admin)
                <button type="button"
                    class="approve-rule-btn inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700 transition"
                    data-rule-id="{{ $rule->id }}">
                    <x-heroicon-o-check class="h-3.5 w-3.5" />
                    Approve
                </button>
            @endif

            {{-- Edit --}}
            <button type="button"
                class="edit-rule-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                data-rule-id="{{ $rule->id }}">
                <x-heroicon-o-pencil class="h-3.5 w-3.5" />
                Edit
            </button>

            {{-- Delete --}}
            <form method="POST"
                action="{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.destroy', $rule->id) }}"
                class="delete-rule-form">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20 transition">
                    <x-heroicon-o-trash class="h-3.5 w-3.5" />
                </button>
            </form>
        </div>
    </div>
</div>
