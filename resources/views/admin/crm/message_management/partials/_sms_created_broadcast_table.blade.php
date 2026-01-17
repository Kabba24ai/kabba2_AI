<!-- TABLE -->
<div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

<table class="min-w-full divide-y divide-gray-200 text-sm">

        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Content Cat</th>
                <th class="py-4 px-6 text-left">Content Name</th>
                <th class="py-4 px-6 text-left">Content</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Created</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Actions</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">


            <!-- ========================== -->
            <!-- ROW 1 — SMS BROADCAST -->
            <!-- ========================== -->
        @forelse($broadcasts as $broadcast)
            <tr class="hover:bg-gray-50 transition">
                <td class="py-4 px-6 whitespace-nowrap text-gray-900"> {{ $broadcast->category->name ?? 'No Category' }}</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                     {{ $broadcast->name }}
                </td>

                <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                   {{ $broadcast->description }}
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">   {{ App\Helpers\CustomHelper::formatDate($broadcast->created_at) ?? 'N/A' }} </div>
                    <div class="text-sm text-gray-500 mt-1"> {{ App\Helpers\CustomHelper::formatTime($broadcast->created_at) ?? 'N/A' }} </div>
                </td>

                <!-- ACTION BUTTONS -->
                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center space-x-3">
                        
                        <!-- EDIT BUTTON -->
                        <button type="button" class="edit-smsbrod-btn text-grey-600 flex items-center text-xs"
                                title="Edit Broadcast" data-id="{{ $broadcast->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide w-5 h-5">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>


                      <!-- DELETE (Red Button) -->
                        <button type="button"
                                class="delete-smsbroadcast-btn text-red-600 flex items-center text-xs"
                                data-id="{{ $broadcast->id }}"
                                data-name="{{ $broadcast->name }}"
                                title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>
            @empty

            <tr>
                <td colspan="6" class="py-10 text-center text-gray-500">
                    No created broadcast messages found.
                </td>
            </tr>

            @endforelse

    </tbody>

</table>

</div>


{{-- Pagination --}}
@if($broadcasts)
<div class="mt-6">
    {{ $broadcasts->links() }}
</div>
@endif


