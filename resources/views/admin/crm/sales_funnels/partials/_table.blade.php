<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3">Funnel Name</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Trigger Event</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">

            @forelse ($funnels as $funnel)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800"
                    id="funnel-row-{{ $funnel->unique_id }}">

                    <td class="px-4 py-4 font-medium text-gray-800 dark:text-gray-100">
                        {{ $funnel->funnel_name }}
                    </td>

                    <td class="px-4 py-4 text-gray-600 dark:text-gray-300">
                        {{ $funnel->category?->category_name ?? '—' }}
                    </td>

                    <td class="px-4 py-4 text-gray-600 dark:text-gray-300">
                        {{ ucfirst(str_replace('_', ' ', $funnel->trigger_event)) }}
                    </td>

                    <td class="px-4 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                            {{ $funnel->status === 'Active'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-gray-200 text-gray-700' }}">
                            {{ $funnel->status }}
                        </span>
                    </td>

                    <td class="px-4 py-4 text-right whitespace-nowrap">
                        <div class="flex justify-end gap-x-3">
                            <button
                                type="button"
                                class="funnel-edit-button text-blue-600 hover:text-blue-800"
                                data-unique-id="{{ $funnel->unique_id }}"
                                title="Edit Funnel">
                                <x-heroicon-o-pencil class="w-5 h-5"/>
                            </button>


                           <button
                                type="button"
                                class="funnel-delete-button text-red-600 hover:text-red-800"
                                title="Delete Funnel"
                                data-unique-id="{{ $funnel->unique_id }}">
                                <x-heroicon-o-trash class="w-4 h-4"/>
                            </button>

                        </div>
                    </td> 
                </tr>

            @empty
                {{-- EMPTY STATE --}}
                <tr>
                    <td colspan="5" class="px-4 py-16">
                        <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12">
                            <div class="mx-auto flex max-w-md flex-col items-center text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                    <svg class="h-7 w-7 text-slate-400"
                                         fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>

                                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                                    No funnels yet
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Create your first funnel to get started
                                </p>

                                <button type="button"
                                        data-open-funnel-modal
                                        class="mt-6 inline-flex items-center rounded-lg
                                               bg-blue-600 px-5 py-2.5 text-sm
                                               font-medium text-white hover:bg-blue-700">
                                    Create Your First Funnel
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse

        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($funnels)
    <div class="mt-6">
        {{ $funnels->links() }}
    </div>
@endif
