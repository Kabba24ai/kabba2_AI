<ul class="sortable-list {{ $depth > 0 ? 'ml-7 mt-1 space-y-0.5 border-l border-gray-100 pl-3' : 'space-y-0.5 p-3' }}"
    data-depth="{{ $depth ?? 0 }}">
    @forelse($items as $item)
        @include('admin.website_management.navigation_builder.partials._tree_node', [
            'item'  => $item,
            'depth' => ($depth ?? 0),
        ])
    @empty
        @if(($depth ?? 0) === 0)
        <li class="empty-root py-10 text-center text-sm text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
            <x-heroicon-o-bars-3 class="w-8 h-8 text-gray-300 mx-auto mb-2"/>
            No items yet. Click <strong>+ Add Item</strong> to get started.
        </li>
        @else
        <li class="empty-placeholder py-2 text-center text-xs text-gray-300 border border-dashed border-gray-200 rounded-md">
            Drop here
        </li>
        @endif
    @endforelse
</ul>
