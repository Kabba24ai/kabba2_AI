<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3">Category Name</th>
                <th class="px-4 py-3">Description</th>
                <th class="px-4 py-3">Color</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <div id="categories-loading" class="hidden"></div>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
            @forelse ($categories as $category)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800" id="category-row-{{ $category->unique_id }}">
                    <td class="px-4 py-4 text-left">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $category->category_name }}</div>
                    </td>
                    <td class="px-4 py-4 text-left">{{ $category->description }}</td>
                    <td class="px-4 py-4 text-left">
                        <span
                            class="inline-block w-6 h-6 rounded-full"
                            style="background-color: {{ $category->color_code }};">
                        </span>
                    </td>
                    <td class="px-4 py-4 text-right whitespace-nowrap">
                        <div class="flex float-right gap-x-3">
                            <button type="button"
                                data-unique_id="{{ $category->unique_id }}"
                                data-color_code="{{ $category->color_code }}"
                                data-category_name="{{ $category->category_name }}"
                                data-description="{{ $category->description }}"
                                class="edit-category-button text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Edit Category">
                               
                                <x-heroicon-o-pencil-square class="w-4 h-4 cursor-pointer" />

                            </button>
                            <button type="button" class="category-delete-button text-red-600 hover:text-red-800" title="Delete"
                                data-unique-id="{{ $category->unique_id }}">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                        @if ($categories)
                            <div class="mt-6 rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12">
                                <div class="mx-auto flex max-w-md flex-col items-center text-center">
                                    {{-- folder icon --}}
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                        <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 7a2 2 0 0 1 2-2h5l2 2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No Categories Yet</h3>
                                    <p class="mt-1 text-sm text-slate-500">Create your first category to organize your funnels</p>

                                    <button type="button" data-open-category-modal
                                        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Category
                                    </button>
                                </div>
                            </div>
                        @else
                            <span class="text-gray-400 italic">inhale… exhale… bringing your data to life…</span>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($categories)
    <div class="mt-6">
        {{ $categories->links() }}
    </div>
@endif
