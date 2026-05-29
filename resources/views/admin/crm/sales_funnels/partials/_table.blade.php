@forelse ($funnels as $funnel)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden my-4" data-funnel-card
        data-funnel-id="{{ $funnel->id }}"
        data-funnel-type="{{ $funnel->trigger_type }}"
        id="funnel-row-{{ $funnel->unique_id }}">

        {{-- Header (click to toggle) --}}
        <div class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 cursor-pointer" data-funnel-toggle>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start mb-2">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $funnel->funnel_name }}</h3>
                            <span class="text-sm text-gray-500">
                                {{ $funnel->steps_count }} {{ $funnel->steps_count == 1 ? 'Event' : 'Events' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13 2L3 14h7l-1 8 12-14h-7l-1-6z" />
                                </svg>
                                @php
                                    $funnelTypeLabels = [
                                        'retail_order'    => 'Retail Order',
                                        'rental_schedule' => 'Rental Schedule',
                                        'lead_added'      => 'New Lead',
                                    ];
                                    $funnelTypeLabel = $funnelTypeLabels[$funnel->trigger_type]
                                        ?? str($funnel->trigger_type)->replace('_', ' ')->title();
                                @endphp
                                <span>Funnel Type: {{ $funnelTypeLabel }}</span>
                            </div>
                        </div>

                        <div class="flex justify-start lg:justify-end">
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $funnel->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $funnel->status }}
                            </span>
                        </div>
                    </div>

                    @if ($funnel->description)
                        <p class="text-gray-600 text-sm">{{ $funnel->description }}</p>
                    @endif
                </div>

                {{-- Right icons --}}
                <div class="flex items-center gap-2 shrink-0">
                    @if ($funnel->status === 'Active')
                        <button class="funnel-toggle-button p-2 text-green-400 hover:bg-gray-50 rounded-lg"
                            type="button" title="Toggle Active" data-unique-id="{{ $funnel->unique_id }}">
                            <x-heroicon-o-power class="w-5 h-5" />
                        </button>
                    @else
                        <button class="funnel-toggle-button p-2 text-gray-400 hover:bg-gray-50 rounded-lg"
                            type="button" data-unique-id="{{ $funnel->unique_id }}" title="Toggle Inactive">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v9m6.364-6.364a9 9 0 11-12.728 0" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4l16 16" />
                            </svg>
                        </button>
                    @endif
                    <button class="funnel-edit-button p-2 text-blue-600 hover:bg-blue-50 rounded-lg" type="button"
                        title="Edit" data-unique-id="{{ $funnel->unique_id }}">
                        <x-heroicon-o-pencil-square class="w-5 h-5 cursor-pointer" />
                    </button>
                    <button class="funnel-duplicate-button p-2 text-gray-600 hover:bg-gray-50 rounded-lg" type="button"
                        title="Duplicate" data-unique-id="{{ $funnel->unique_id }}">
                        <x-heroicon-o-document-duplicate class="w-5 h-5" />
                    </button>
                    <button class="funnel-delete-button p-2 text-red-600 hover:bg-red-50 rounded-lg" type="button"
                        title="Delete" data-unique-id="{{ $funnel->unique_id }}">
                        <x-heroicon-o-trash class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Expand area (hidden by default) --}}
        <div class="border-t border-gray-200 bg-gray-50 p-6 hidden" data-funnel-panel aria-hidden="true">
            @if (count($funnel->steps) === 0)
                {{-- Empty state --}}
                <div class="text-center py-14">
                    <div class="text-gray-300 mb-4">
                        <svg class="w-14 h-14 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Build Your Funnel</h4>
                    <p class="text-gray-600 mb-6">Add events to create an automated sequence of messages</p>

                    <button type="button"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                        data-open-add-step
                        data-funnel-id="{{ $funnel->id }}"
                        data-funnel-unique-id="{{ $funnel->unique_id }}"
                        data-funnel-type="{{ $funnel->trigger_type }}">
                        Add First Event
                    </button>
                </div>
            @else
                @include('admin.crm.sales_funnels.partials._steps_timeline', ['steps' => $funnel->steps])
            @endif
        </div>
    </div>
@empty
    {{-- EMPTY STATE --}}
    @if ($funnels)
        <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12">
            <div class="mx-auto flex max-w-md flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    No funnels yet
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Create your first funnel to get started
                </p>

                <button type="button" data-open-funnel-modal
                    class="mt-6 inline-flex items-center rounded-lg
                           bg-blue-600 px-5 py-2.5 text-sm
                           font-medium text-white hover:bg-blue-700">
                    Create Your First Funnel
                </button>
            </div>
        </div>
    @else
        <div class="flex items-center justify-center py-12">
            <span class="text-gray-400 italic">inhale… exhale… bringing your data to life…</span>
        </div>
    @endif
@endforelse

{{-- Pagination --}}
@if ($funnels)
    <div class="mt-6">
        {{ $funnels->links() }}
    </div>
@endif


@push('js')
    <script>
        document.addEventListener('click', (e) => {
            // If user clicked inside any action button, do NOT toggle
            if (e.target.closest('button, a, input, select, textarea, [data-no-toggle]')) return;

            // Find the nearest toggle area that was clicked
            const toggle = e.target.closest('[data-funnel-toggle]');
            if (!toggle) return;

            // Find the card and its panel
            const card = toggle.closest('[data-funnel-card]');
            if (!card) return;

            const panel = card.querySelector('[data-funnel-panel]');
            if (!panel) return;

            // Toggle
            const isHidden = panel.getAttribute('aria-hidden') === 'true';
            panel.setAttribute('aria-hidden', String(!isHidden));
            panel.classList.toggle('hidden', !isHidden);
        });
    </script>
@endpush
