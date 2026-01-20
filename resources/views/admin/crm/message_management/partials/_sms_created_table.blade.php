<!-- TABLE -->
<div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

<table class="min-w-full divide-y divide-gray-200 text-sm">

        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Content Cat</th>
                <th class="py-4 px-6 text-left">Content Name</th>
                <th class="py-4 px-6 text-left">Type</th>
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
                <td class="py-4 px-6 whitespace-nowrap text-gray-900"> {{ $broadcast->category_name ?? 'No Category' }}</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                     {{ $broadcast->name }}
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    @if($broadcast->type === 'broadcast')
                        <span class="px-2 py-1 text-xs rounded bg-orange-100 text-orange-700">
                            Broadcast
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                            Funnel
                        </span>
                    @endif
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

                    {{-- ========================= --}}
                    {{-- BROADCAST ACTIONS --}}
                    {{-- ========================= --}}
                    @if($broadcast->type === 'broadcast')

                       <!-- COPY (Green Button) -->
                        <a href="{{ route('admin.crm.message-management.sms-broadcast.copy', $broadcast->id) }}" class="text-green-600 flex items-center text-xs" title="Copy Message" >
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                            </a>

                        {{-- EDIT BROADCAST --}}
                        <button type="button"
                            class="edit-smsbrod-btn text-grey-600 flex items-center text-xs"
                            title="Edit Broadcast"
                            data-id="{{ $broadcast->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide w-5 h-5">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        {{-- DELETE BROADCAST --}}
                        <button type="button"
                            class="delete-smsbroadcast-btn text-red-600 flex items-center text-xs"
                            data-id="{{ $broadcast->id }}"
                            data-name="{{ $broadcast->name }}"
                            title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107
                                    1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0
                                    1-2.244 2.077H8.084a2.25 2.25 0 0
                                    1-2.244-2.077L4.772 5.79m14.456 0
                                    a48.108 48.108 0 0 0-3.478-.397m-12 .562
                                    c.34-.059.68-.114 1.022-.165m0 0
                                    a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916
                                    c0-1.18-.91-2.164-2.09-2.201a51.964
                                    51.964 0 0 0-3.32 0c-1.18.037-2.09
                                    1.022-2.09 2.201v.916m7.5 0a48.667
                                    48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    {{-- ========================= --}}
                    {{-- FUNNEL ACTIONS --}}
                    {{-- ========================= --}}
                    @else

                        <!-- COPY (Green Button) -->
                        <a href="{{ route('admin.crm.message-management.sms-funnel.copy', $broadcast->id) }}" class="text-green-600 flex items-center text-xs" title="Copy Message" >
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                        </a>


                        {{-- EDIT FUNNEL (CREDITS) --}}
                        <button type="button"
                            class="edit-sms-funnel-btn text-grey-600 flex items-center text-xs"
                            title="Credits"
                            data-id="{{ $broadcast->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-credit-card w-5 h-5">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        {{-- DELETE FUNNEL --}}
                        <button type="button"
                            class="delete-sms-funnel-btn text-red-600 flex items-center text-xs"
                            title="Delete"
                            data-id="{{ $broadcast->id }}"
                            data-name="{{ $broadcast->name }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21
                                    c.342.052.682.107 1.022.166m-1.022-.165
                                    L18.16 19.673a2.25 2.25 0 0
                                    1-2.244 2.077H8.084a2.25 2.25 0 0
                                    1-2.244-2.077L4.772 5.79m14.456 0
                                    a48.108 48.108 0 0 0-3.478-.397m-12 .562
                                    c.34-.059.68-.114 1.022-.165m0 0
                                    a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916
                                    c0-1.18-.91-2.164-2.09-2.201a51.964
                                    51.964 0 0 0-3.32 0c-1.18.037-2.09
                                    1.022-2.09 2.201v.916m7.5 0a48.667
                                    48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    @endif
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


