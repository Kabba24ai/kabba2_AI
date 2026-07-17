{{-- Message Library table — reusable content only, no send state --}}
<div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

    <table class="min-w-full divide-y divide-gray-200 text-sm">

        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Category</th>
                <th class="py-4 px-6 text-left">Message Name</th>
                <th class="py-4 px-6 text-left">Content</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Created</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Updated</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Actions</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($broadcasts as $broadcast)
            <tr class="hover:bg-gray-50 transition">
                <td class="py-4 px-6 whitespace-nowrap text-gray-900">{{ $broadcast->category->name ?? 'No Category' }}</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                    {{ $broadcast->name }}
                    @if ($broadcast->archived_at)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">Archived</span>
                    @endif
                </td>

                <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                    {{ $broadcast->description }}
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ App\Helpers\CustomHelper::formatDate($broadcast->created_at) ?? 'N/A' }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ $broadcast->createdBy?->full_name ?? '—' }}</div>
                </td>

                <td class="py-4 px-6 whitespace-nowrap text-gray-700">
                    {{ App\Helpers\CustomHelper::formatDate($broadcast->updated_at) ?? 'N/A' }}
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center space-x-3">

                        @unless ($broadcast->archived_at)
                        <!-- USE IN BROADCAST -->
                        <a href="{{ route('admin.crm.message-management.broadcast-wizard.create', ['message' => $broadcast->id]) }}"
                           class="text-orange-500 flex items-center text-xs" title="Use in Broadcast">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M22 2L11 13" />
                                <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                            </svg>
                        </a>

                        <!-- EDIT -->
                        <button type="button" class="edit-smsbrod-btn text-gray-600 flex items-center text-xs"
                                title="Edit Message" data-id="{{ $broadcast->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        <!-- COPY -->
                        <a href="{{ route('admin.crm.message-management.sms-broadcast.copy', $broadcast->id) }}"
                           class="text-green-600 flex items-center text-xs" title="Copy Message">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"/>
                            </svg>
                        </a>
                        @endunless

                        <!-- ARCHIVE / RESTORE -->
                        <button type="button"
                                class="archive-smsbroadcast-btn {{ $broadcast->archived_at ? 'text-blue-600' : 'text-amber-600' }} flex items-center text-xs"
                                data-id="{{ $broadcast->id }}"
                                title="{{ $broadcast->archived_at ? 'Restore Message' : 'Archive Message' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                            </svg>
                        </button>

                        <!-- DELETE -->
                        <button type="button"
                                class="delete-smsbroadcast-btn text-red-600 flex items-center text-xs"
                                data-id="{{ $broadcast->id }}"
                                data-name="{{ $broadcast->name }}"
                                title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-10 text-center text-gray-500">
                    No saved messages yet. Create your first reusable SMS message.
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
