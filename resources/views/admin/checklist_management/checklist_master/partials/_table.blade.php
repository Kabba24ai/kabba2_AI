<!-- Table -->
    <div class="bg-white border border-gray-200 rounded-md overflow-x-auto mt-6">
        <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Checklist System Name</th>
                    <th class="px-4 py-3 text-left font-semibold">Category</th>
                    <th class="px-4 py-3 text-left font-semibold">Rental Ready</th>
                    <th class="px-4 py-3 text-left font-semibold">Customer Checklist</th>
                    <th class="px-4 py-3 text-left font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-900">

                 @forelse ($checklistMasters as $Master)
                    <tr data-status="{{ $Master->equipment_category_id }}">
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $Master->checklist_system_name }}</td>
                        <td class="px-4 py-3"><span
                                class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">{{ $Master->category->getHierarchyLabel() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                                <div class="text-left">
                                    <div class="text-sm text-gray-900">
                                        {{ $Master->rentalReadyTemplate?->questions?->count() ?? 0 }}
                                        {{ Str::plural('question', $Master->rentalReadyTemplate?->questions?->count() ?? 0) }}
                                    </div>
                                    <a href="{{ route('admin.checklist-management.rental-ready.index', ['template' => $Master->rentalReadyTemplate->template_name ?? '']) }}"
                                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        {{ $Master->rentalReadyTemplate->template_name ?? 'No template assigned' }} </a>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                <div class="text-left">
                                    <div class="text-sm text-gray-900">
                                        {{ $Master->customerAdminTemplate?->questions?->count() ?? 0 }}
                                        {{ Str::plural('question', $Master->customerAdminTemplate?->questions?->count() ?? 0) }}
                                    </div>

                                    @if ($Master->customerAdminTemplate)
                                        <a href="{{ route('admin.checklist-management.customer-admin.index', ['template' => $Master->customerAdminTemplate->template_name]) }}"
                                            class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors"
                                            title="Go to this Customer Admin Template">
                                            {{ $Master->customerAdminTemplate->template_name }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">No template assigned</span>
                                    @endif
                                </div>
                            </div>
                        </td>


                        <td class="px-4 py-3 ">
                            <div class="flex gap-2">

                                <!-- <form
                                    action="{{ route('admin.checklist-management.checklist-master.copy', $Master->unique_id) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800"
                                        title="Copy this Checklist Master">
                                        <x-heroicon-o-clipboard-document class="w-4 h-4" />
                                    </button>
                                </form> -->



                                <a href="{{ route('admin.checklist-management.checklist-master.edit', $Master->unique_id) }}"
                                    class="text-blue-600">
                                    <x-heroicon-o-pencil class="w-4 h-4" />
                                </a>

                                <form
                                    action="{{ route('admin.checklist-management.checklist-master.delete', $Master->unique_id) }}"
                                    method="POST" class="inline delete-checklist-master-form"
                                    data-checklist-master-name="{{ $Master->checklist_system_name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </form>



                            </div>
                        </td>
                    </tr>


                     @empty
        <tr>
            <td colspan="5" class="text-center py-4 text-gray-500">No checklist found.</td>
        </tr>
        @endforelse
            </tbody>
        </table>
    </div>

@if ($checklistMasters)


<div class="mt-4 px-4">
    {{ $checklistMasters->links() }}
</div>

@endif
