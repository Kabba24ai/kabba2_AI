@forelse ($options as $option)
    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
        <td class=" px-6 py-4">{{ $option->name }}</td>
        <td class=" px-6 py-4">{{ $option->type }}</td>
        <td class=" px-6 py-4">{{ $option->description }}</td>
        <td class=" px-6 py-4">{{ $option->items_count }}</td>
        <td class=" px-6 py-4">
            <span
                class="{{ $option->isActive() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $option->isActive() ? 'Active' : 'Inactive' }}
            </span>
        </td>
        <td class="px-6 py-4">
            <div class="flex space-x-2">
                <a href="{{ route('admin.product-management.options.edit', $option->unique_id) }}"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    title="Edit">
                    <x-heroicon-o-pencil-square class="w-5 h-5 cursor-pointer" />
                </a>
                <form action="{{ route('admin.product-management.options.delete', $option->unique_id) }}"
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
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center px-6 py-10 text-gray-500 dark:text-gray-400">
            No Rental Options Found.
            <a href="{{ route('admin.product-management.options.create') }}"
                class="text-brand-600 hover:underline dark:text-brand-400">Create Your First One</a>
        </td>
    </tr>
@endforelse
