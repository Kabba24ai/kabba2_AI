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

    {{-- Main Container --}}
    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Table Card --}}
        <div class="flex-1 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:bg-gray-900 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Description</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Options Count</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-950">
                    <tr>
                        <td colspan="6" class="text-center px-6 py-10 text-gray-500 dark:text-gray-400">
                            No Rental Options Found.
                            <a href="#" class="text-brand-600 hover:underline dark:text-brand-400">Create Your First One</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Sidebar Quick Stats --}}
        <div class="w-full lg:w-1/4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-white mb-4">Quick Stats</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex justify-between">
                    <span>Total Lists:</span>
                    <span class="font-medium">0</span>
                </li>
                <li class="flex justify-between">
                    <span>Active Lists:</span>
                    <span class="font-medium">0</span>
                </li>
                <li class="flex justify-between">
                    <span>Inactive Lists:</span>
                    <span class="font-medium">0</span>
                </li>
            </ul>
        </div>
    </div>
@endsection
