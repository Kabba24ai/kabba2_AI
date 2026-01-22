{{-- Table --}}
<div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <table class="table-fixed w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        {{-- Table Header --}}
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th
                    class="w-2/5 px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Title</th>
                <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Type</th>

                <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Created</th>
                <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Status</th>
                <th class="px-6 py-4 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Actions</th>
            </tr>
        </thead>
        <div id="terms-loading" class="hidden"></div>
        {{-- Table Body --}}
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
            @forelse($terms as $term)
                <tr
                    class="{{ $term->is_global == 'Yes' ? 'bg-gray-200' : '' }} hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $term->title }}</div>
                        <div class="text-gray-500 text-xs truncate">{!! Str::limit(strip_tags($term->content), 50) !!}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $term->is_global === 'Yes'
                                    ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400'
                                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400' }}">
                            {{ $term->is_global == 'Yes' ? 'Global' : 'Product' }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatDate($term->created_at) }}
                    </td>
                    <td class="px-6 py-4">
                        {!! \App\Helpers\CustomHelper::statusBadge($term->status) !!}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <div class="flex float-right gap-x-3">
                            <a href="{{ route('admin.terms-and-conditions.edit', $term->unique_id) }}"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Edit">
                                <x-heroicon-o-pencil class="w-5 h-5" />
                            </a>
                            @if ($term->is_global == 'No')
                                <form action="{{ route('admin.terms-and-conditions.delete', $term->unique_id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this option?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </form>
                            @endif
                        </div>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                        @if ($terms)
                            No terms found
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
@if ($terms)
    <div class="mt-6">
        {{ $terms->links() }}
    </div>
@endif
