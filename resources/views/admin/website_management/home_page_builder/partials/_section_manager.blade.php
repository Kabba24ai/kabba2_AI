{{-- ── Section Manager Panel ──────────────────────────────────────────── --}}
@php
$sectionIconMap = [
    'hero'               => ['icon' => 'heroicon-o-photo',               'color' => 'text-blue-500',   'bg' => 'bg-blue-50'],
    'contact_strip'      => ['icon' => 'heroicon-o-phone',               'color' => 'text-green-500',  'bg' => 'bg-green-50'],
    'featured_rentals'   => ['icon' => 'heroicon-o-squares-2x2',         'color' => 'text-purple-500', 'bg' => 'bg-purple-50'],
    'feature_strip'      => ['icon' => 'heroicon-o-star',                'color' => 'text-yellow-500', 'bg' => 'bg-yellow-50'],
    'footer'             => ['icon' => 'heroicon-o-bars-3-bottom-right', 'color' => 'text-gray-500',   'bg' => 'bg-gray-100'],
    'seo'                => ['icon' => 'heroicon-o-magnifying-glass',    'color' => 'text-teal-500',   'bg' => 'bg-teal-50'],
    'branding'           => ['icon' => 'heroicon-o-paint-brush',         'color' => 'text-pink-500',   'bg' => 'bg-pink-50'],
    'newsletter'         => ['icon' => 'heroicon-o-envelope',            'color' => 'text-red-500',    'bg' => 'bg-red-50'],
    'rental_specialists' => ['icon' => 'heroicon-o-users',               'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50'],
];
$editableTabs = ['hero', 'contact_strip', 'featured_rentals', 'feature_strip', 'footer', 'seo', 'branding'];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">

    {{-- Panel header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
            <x-heroicon-o-squares-2x2 class="w-5 h-5 text-blue-600"/>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Section Manager</h2>
                <p class="text-xs text-gray-400">Drag to reorder · toggle to show/hide on the homepage</p>
            </div>
        </div>
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium">
            {{ $allSectionsOrdered->count() }} sections
        </span>
    </div>

    @if($allSectionsOrdered->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-400">
            No sections found. Run: <code class="font-mono bg-gray-100 px-1 rounded">php artisan db:seed --class=HomePageBuilderSeeder</code>
        </div>
    @else
        <p class="text-xs text-gray-400 px-5 pt-3 pb-1 flex items-center gap-1">
            <x-heroicon-o-arrows-up-down class="w-3.5 h-3.5"/>
            Drag rows to reorder — order saves automatically via AJAX.
        </p>

        <div id="section-manager-cards" class="divide-y divide-gray-50">
            @foreach($allSectionsOrdered as $section)
            @php
                $meta  = $sectionIconMap[$section->section_key] ?? ['icon' => 'heroicon-o-squares-plus', 'color' => 'text-gray-400', 'bg' => 'bg-gray-50'];
                $label = $section->section_name ?? ucwords(str_replace('_', ' ', $section->section_key));
            @endphp
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/50 transition-colors"
                 data-section-id="{{ $section->unique_id }}"
                 x-data="{ active: @js($section->status === 'Active') }">

                {{-- Status toggle form — hidden, submitted by Alpine --}}
                <form method="POST"
                      action="{{ route('admin.website-management.home-builder.section.update', $section->unique_id) }}"
                      x-ref="sf" class="hidden">
                    @csrf
                    <input type="hidden" name="status" :value="active ? 'Active' : 'Inactive'">
                </form>

                {{-- Drag handle --}}
                <span class="section-drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0 select-none">
                    <x-heroicon-o-bars-3 class="w-4 h-4"/>
                </span>

                {{-- Section icon --}}
                <div class="w-8 h-8 rounded-lg {{ $meta['bg'] }} flex items-center justify-center shrink-0">
                    <x-dynamic-component :component="$meta['icon']" class="w-4 h-4 {{ $meta['color'] }}"/>
                </div>

                {{-- Label + order --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $label }}</p>
                    <p class="text-xs text-gray-400 section-order-label">order: {{ $section->display_order }}</p>
                </div>

                {{-- Status pill --}}
                <span :class="active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                      class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0 hidden sm:inline-block"
                      x-text="active ? 'Active' : 'Inactive'"></span>

                {{-- Toggle switch --}}
                <button type="button"
                        @click="active = !active; $nextTick(() => $refs.sf.submit())"
                        :class="active ? 'bg-green-500' : 'bg-gray-300'"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-400">
                    <span :class="active ? 'translate-x-4' : 'translate-x-0.5'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"></span>
                </button>

                {{-- Edit shortcut --}}
                @if(in_array($section->section_key, $editableTabs))
                <a href="{{ route('admin.website-management.home-builder.index', ['tab' => $section->section_key]) }}"
                   class="text-xs text-blue-600 hover:text-blue-800 hover:underline shrink-0">
                    Edit ↓
                </a>
                @endif

            </div>
            @endforeach
        </div>
    @endif
</div>
