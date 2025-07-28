<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3">Store Name</th>
                <th class="px-4 py-3">Phone</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Address</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <div id="stores-loading" class="hidden"></div>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
            @forelse ($stores as $store)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-4 py-4 text-left">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $store->store_name }}</div>
                        @if ($store->is_primary === 'Yes')
                            <span
                                class="ml-0 inline-block rounded bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 px-2 py-0.5 text-xs font-semibold align-middle">
                                Primary
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">{{ $store->phone }}</td>
                    <td class="px-4 py-4 whitespace-nowrap">{{ $store->email }}</td>
                    <td class="px-4 py-4 truncate min-w-xs max-w-xs">{{ $store->full_address }}</td>

                    <td class="px-4 py-4 whitespace-nowrap text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($store->status) !!}
                    </td>
                    <td class="px-4 py-4 text-right whitespace-nowrap">
                        <div class="flex float-right gap-x-3">
                            <a href="{{ route('admin.stores.edit', $store->unique_id) }}"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Edit">
                                <x-heroicon-o-pencil class="w-5 h-5" />
                            </a>
                            <form action="{{ route('admin.stores.delete', $store->unique_id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this store?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    title="Delete">
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">No stores found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-6">
    {{ $stores->links() }}
</div>
