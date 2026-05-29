{{-- AI Profiles Table for selected category --}}
<div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                AI Profiles — {{ $selectedCategory->title }}
            </h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                {{ $profiles->count() }} unique make/model profile(s) found
            </p>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Make</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Model</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">AI Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last AI Update</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Specs</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($profiles as $profile)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        {{-- Make --}}
                        <td class="px-5 py-4 font-medium text-gray-900 dark:text-gray-100">
                            {{ $profile->make }}
                        </td>

                        {{-- Model --}}
                        <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                            {{ $profile->model ?: '—' }}
                        </td>

                        {{-- AI Status badge --}}
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $profile->statusBadgeClass() }}">
                                {{ $profile->statusLabel() }}
                            </span>
                        </td>

                        {{-- Last update --}}
                        <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                            {{ $profile->last_ai_update_at ? $profile->last_ai_update_at->diffForHumans() : '—' }}
                        </td>

                        {{-- Spec count --}}
                        <td class="px-5 py-4 text-center">
                            @php $specCount = $profile->specifications->count(); @endphp
                            @if ($specCount > 0)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                    {{ $specCount }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">0</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                {{-- View / Edit Specs --}}
                                <a href="{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profile->unique_id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="View / Edit Specifications">
                                    <x-heroicon-o-clipboard-document-list class="h-4 w-4" />
                                    Specs
                                </a>

                                {{-- Mark Needs Review --}}
                                <form method="POST"
                                    action="{{ route('admin.maintenance-management.equipment-ai.specifications.update', $profile->unique_id) }}"
                                    class="inline">
                                    @csrf
                                    @method('PUT')
                                </form>

                                {{-- Delete --}}
                                <form method="POST"
                                    action="{{ route('admin.maintenance-management.equipment-ai.profiles.delete', $profile->unique_id) }}"
                                    class="inline"
                                    onsubmit="return confirm('Delete AI profile for {{ addslashes($profile->make) }} {{ addslashes($profile->model) }}? All specifications will also be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete Profile">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                            <x-heroicon-o-cpu-chip class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                            No AI profiles found for this category.
                            Use <strong>Scan Category</strong> above to generate them from existing equipment records.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
