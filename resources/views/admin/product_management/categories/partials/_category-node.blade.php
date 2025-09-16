<li class="sortable-category-item" data-id="{{ $category->id }}">
    <div
        class="overflow-hidden flex items-center justify-between bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 shadow-sm hover:shadow-md transition group min-w-0">
        <div class="flex items-center gap-3 min-w-0">
            <!-- Drag Handle -->
            <div data-drag-handle class="text-gray-400 hover:text-gray-600 cursor-move shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600"
                    fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="5" cy="5" r="1.5" />
                    <circle cx="10" cy="5" r="1.5" />
                    <circle cx="5" cy="10" r="1.5" />
                    <circle cx="10" cy="10" r="1.5" />
                    <circle cx="5" cy="15" r="1.5" />
                    <circle cx="10" cy="15" r="1.5" />
                </svg>
            </div>

            <!-- Title -->
            <span class="flex-1 min-w-0 truncate text-gray-800 dark:text-white font-medium">
                {{ html_entity_decode($category->title) }}
            </span>

            <div class="shrink-0">
                <!-- Status -->
                {!! \App\Helpers\CustomHelper::statusBadge($category->status) !!}
            </div>

        </div>

        <div class="relative flex items-center shrink-0">
            <!-- More actions button: hidden on desktop -->
            <button class="block md:hidden p-1 hover:bg-gray-200 rounded"
                onclick="toggleCategoryActionsMenu(event, '{{ $category->id }}')" aria-label="Show Actions">
                <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
            </button>

            <!-- Action Icons Menu -->
            <div id="category-actions-{{ $category->id }}"
                class="absolute right-0 top-7 z-30 bg-white dark:bg-gray-800 rounded shadow flex flex-col items-center
               p-1 min-w-[40px] gap-1
               invisible opacity-0 transition-opacity duration-150
               md:static md:flex-row md:gap-2 md:p-0 md:bg-transparent md:shadow-none md:visible md:opacity-100
               text-gray-400">
                {{-- <a href="#" title="View" class="hover:text-blue-500 flex items-center justify-center">
                    <x-heroicon-o-eye class="w-5 h-5" />
                </a> --}}
                @if ($addFlag)
                    <a href="{{ route('admin.product-management.categories.create', ['categoryId' => $category->id]) }}"
                        title="Add Subcategory" class="hover:text-green-500 flex items-center justify-center">
                        <x-heroicon-o-plus-circle class="w-5 h-5" />
                    </a>
                @endif
                <a href="{{ route('admin.product-management.categories.edit', $category->unique_id) }}" title="Edit"
                    class="hover:text-blue-500 flex items-center justify-center">
                    <x-heroicon-o-pencil-square class="w-5 h-5" />
                </a>
                <form method="POST"
                    action="{{ route('admin.product-management.categories.delete', $category->unique_id) }}"
                    onsubmit="return confirm('Are you sure you want to delete this category?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Delete" class="hover:text-red-500 flex items-center justify-center">
                        <x-heroicon-o-trash class="w-5 h-5" />
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($category->childCategories->isNotEmpty())
        <ul class="nested-sortable mt-2 ml-6 space-y-3 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
            @foreach ($category->childCategories as $child)
                @include('admin.product_management.categories.partials._category-node', [
                    'category' => $child,
                    'addFlag' => false,
                ])
            @endforeach
        </ul>
    @endif
</li>

@push('js')
    <script>
        // Add once in your scripts section or layout!
        function toggleCategoryActionsMenu(event, id) {
            event.stopPropagation();
            // Hide all other menus first
            document.querySelectorAll('[id^="category-actions-"]').forEach(menu => {
                menu.classList.add('invisible', 'opacity-0');
                menu.classList.remove('visible', 'opacity-100');
            });
            // Show this one
            const menu = document.getElementById(`category-actions-${id}`);
            menu.classList.toggle('invisible');
            menu.classList.toggle('opacity-0');
            menu.classList.toggle('visible');
            menu.classList.toggle('opacity-100');
        }
        // Hide menus if clicking elsewhere
        document.addEventListener('click', () => {
            document.querySelectorAll('[id^="category-actions-"]').forEach(menu => {
                menu.classList.add('invisible', 'opacity-0');
                menu.classList.remove('visible', 'opacity-100');
            });
        });
    </script>
@endpush
