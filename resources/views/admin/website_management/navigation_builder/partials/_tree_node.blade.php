@php
$typeBadge = match($item->type) {
    'internal_page' => ['label' => 'Page',     'class' => 'bg-blue-100 text-blue-700'],
    'external_url'  => ['label' => 'URL',      'class' => 'bg-purple-100 text-purple-700'],
    'anchor'        => ['label' => 'Anchor',   'class' => 'bg-amber-100 text-amber-700'],
    'email'         => ['label' => 'Email',    'class' => 'bg-green-100 text-green-700'],
    'phone'         => ['label' => 'Phone',    'class' => 'bg-teal-100 text-teal-700'],
    default         => ['label' => $item->type, 'class' => 'bg-gray-100 text-gray-500'],
};
$isInactive  = $item->status === 'Inactive';
$resolvedUrl = $item->resolved_url;
@endphp

<li class="tree-item rounded-md {{ $isInactive ? 'opacity-60' : '' }}"
    data-id="{{ $item->unique_id }}">

    {{-- Serialized item data (read by JS for edit panel) --}}
    <script type="application/json" class="item-data">@json($item->toBuilderArray())</script>

    <div class="item-row group flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 rounded-md transition-colors">

        {{-- Drag handle --}}
        <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0 select-none"
              title="Drag to reorder">
            <x-heroicon-o-bars-3 class="w-4 h-4"/>
        </span>

        {{-- Expand/collapse (only if children exist) --}}
        @if($item->children->isNotEmpty())
            <button type="button"
                    class="tree-toggle shrink-0 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                    onclick="navTreeToggle(this)" aria-expanded="true">
                <x-heroicon-o-chevron-down class="w-3.5 h-3.5 transition-transform duration-150"/>
            </button>
        @else
            <span class="w-3.5 h-3.5 shrink-0 text-gray-200">
                <x-heroicon-o-minus class="w-3.5 h-3.5"/>
            </span>
        @endif

        {{-- Icon --}}
        @if($item->icon)
            <span class="shrink-0 text-gray-400 w-4 h-4 flex items-center justify-center text-xs" aria-hidden="true">
                @if(str_starts_with($item->icon, 'heroicon'))
                    <x-dynamic-component :component="$item->icon" class="w-4 h-4"/>
                @else
                    <i class="{{ $item->icon }}"></i>
                @endif
            </span>
        @endif

        {{-- Title + URL --}}
        <div class="flex-1 min-w-0">
            <span class="text-sm font-medium text-gray-800 truncate block {{ $isInactive ? 'line-through' : '' }}">
                {{ $item->title }}
            </span>
            @if($resolvedUrl)
                <span class="text-xs text-gray-400 truncate block">{{ $resolvedUrl }}</span>
            @endif
        </div>

        {{-- Type badge --}}
        <span class="hidden sm:inline-flex shrink-0 text-xs px-1.5 py-0.5 rounded font-medium {{ $typeBadge['class'] }}">
            {{ $typeBadge['label'] }}
        </span>

        {{-- Visibility badge --}}
        @if($item->visibility !== 'both')
            <span class="hidden md:inline-flex shrink-0 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">
                {{ $item->visibility === 'desktop_only' ? '🖥 Desktop' : '📱 Mobile' }}
            </span>
        @endif

        {{-- Opens in new tab --}}
        @if($item->target === '_blank')
            <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 text-gray-300 shrink-0" title="Opens in new tab"/>
        @endif

        {{-- Status dot --}}
        <span class="w-2 h-2 rounded-full shrink-0 {{ $isInactive ? 'bg-gray-300' : 'bg-green-400' }}"
              title="{{ $item->status }}"></span>

        {{-- Hover actions --}}
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            <button type="button" data-action="edit"
                    class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit">
                <x-heroicon-o-pencil-square class="w-3.5 h-3.5 pointer-events-none"/>
            </button>
            <button type="button" data-action="add-child"
                    class="p-1 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors" title="Add child item">
                <x-heroicon-o-plus class="w-3.5 h-3.5 pointer-events-none"/>
            </button>
            <button type="button" data-action="duplicate"
                    class="p-1 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors" title="Duplicate">
                <x-heroicon-o-document-duplicate class="w-3.5 h-3.5 pointer-events-none"/>
            </button>
            <button type="button" data-action="toggle"
                    class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
                    title="{{ $isInactive ? 'Activate' : 'Deactivate' }}">
                @if($isInactive)
                    <x-heroicon-o-eye-slash class="w-3.5 h-3.5 pointer-events-none"/>
                @else
                    <x-heroicon-o-eye class="w-3.5 h-3.5 pointer-events-none"/>
                @endif
            </button>
            <button type="button" data-action="delete"
                    class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete">
                <x-heroicon-o-trash class="w-3.5 h-3.5 pointer-events-none"/>
            </button>
        </div>

    </div>

    {{-- Children (recursive — always rendered, even if empty, for drop targets) --}}
    @include('admin.website_management.navigation_builder.partials._tree', [
        'items' => $item->children,
        'depth' => ($depth ?? 0) + 1,
    ])

</li>
