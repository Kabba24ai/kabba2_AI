@extends('admin.layouts.app')

@section('title', 'Rental Options Lists')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Rental Options</h3>
        <a href="{{ route('admin.product-management.options.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Options
        </a>
    </div>

    @include('flash::message')
    {{-- Main Container --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Table Card --}}
        <div class="flex-1 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:bg-gray-900 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Name
                        </th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Type
                        </th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Description</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Options Count</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Status</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-950">
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
                                    <a href="{{ route('admin.product-management.options.edit', $option->unique_id   ) }}"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="Edit">
                                        
                                <x-heroicon-o-pencil-square class="w-5 h-5 cursor-pointer" />

                                    </a>
                                    <form action="{{ route('admin.product-management.options.delete', $option->unique_id    ) }}"
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
                </tbody>
            </table>
        </div>

        {{-- Sidebar Quick Stats --}}
        <div
            class="w-full lg:w-1/4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-white mb-4">Quick Stats</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex justify-between">
                    <span>Total Lists:</span>
                    <span class="font-medium">{{ $totalOptions }}</span>
                </li>
                <li class="flex justify-between">
                    <span>Active Lists:</span>
                    <span class="font-medium">{{ $activeOptions }}</span>
                </li>
                <li class="flex justify-between">
                    <span>Inactive Lists:</span>
                    <span class="font-medium">{{ $inactiveOptions }}</span>
                </li>
            </ul>
        </div>
    </div>
@endsection
