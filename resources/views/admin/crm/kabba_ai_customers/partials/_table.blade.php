<div class="shadow rounded-2xl overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Date</th>
                <th class="py-4 px-6 text-left">Schedule DateTime</th>
                <th class="py-4 px-6 text-left">Name</th>
                <th class="py-4 px-6 text-left">Email</th>
                <th class="py-4 px-6 text-left">Phone</th>
                <th class="py-4 px-6 text-left">Business</th>
                <th class="py-4 px-6 text-left">Amount</th>
                <th class="py-4 px-6 text-center">Payment Status</th>
                <th class="py-4 px-6 text-left">Demo Status</th>
                <th class="py-4 px-6 text-center">Action</th>
            </tr>
        </thead>
        <div id="kabba-ai-customers-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($submissions as $submission)
                <tr id="submission-row-{{ $submission->unique_id }}" class="hover:bg-gray-50">
                    <td class="py-4 px-6">{{ optional($submission->created_at)->format(config('app.date.date_format') . ' h:i A') ?: '-' }}</td>
                    <td class="py-4 px-6">
                        @if($submission->schedule_datetime)
                            @php
                                $scheduleDate = \Carbon\Carbon::parse($submission->schedule_datetime);
                                $isUpcoming = $scheduleDate->isFuture();
                            @endphp
                            <span class="px-3 py-2 rounded-lg font-medium {{ $isUpcoming ? 'bg-yellow-100 text-yellow-800 font-semibold' : 'text-gray-600' }}">
                                {{ $scheduleDate->format(config('app.date.date_format') . ' h:i A') }}
                            </span>
                        @else
                            <span class="text-gray-400 italic">-</span>
                        @endif
                    </td>
                    <td class="py-4 px-6">{{ trim(($submission->first_name ?? '') . ' ' . ($submission->last_name ?? '')) ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->email ?: '-' }}</td>
                    <td class="py-4 px-6">{{ \App\Helpers\CustomHelper::formatPhone($submission->phone_number) ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->business_name ?: '-' }}</td>
                    <td class="py-4 px-6">${{ number_format((float) $submission->amount, 2) }}</td>
                    <td class="py-4 px-6">
                        @php
                            $statusClasses = match(strtolower($submission->status ?? '')) {
                                'active', 'completed', 'approved' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'cancelled', 'failed', 'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClasses }}">
                            {{ ucfirst($submission->status ?? '-') }}
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        @php
                            $demoStatus = (string) ($submission->setup_status ?? 'pending');
                            $demoStatusClasses = match(strtolower($demoStatus)) {
                                'completed' => 'bg-green-100 text-green-700',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $demoStatusClasses }}">
                            {{ ucwords(str_replace('_', ' ', $demoStatus)) }}
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.crm.kabba-ai-customers.show', $submission->unique_id) }}"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium text-xs">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('admin.crm.kabba-ai-customers.edit', $submission->unique_id) }}"
                               class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-800 font-medium text-xs">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                            </a>
                            <button type="button"
                                    class="delete-customer-btn inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-medium text-xs"
                                    data-unique-id="{{ $submission->unique_id }}"
                                    data-url="{{ route('admin.crm.kabba-ai-customers.destroy', $submission->unique_id) }}">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-sm text-gray-500 px-4 py-6">
                        @if ($submissions)
                            No kabba.ai customers found.
                        @else
                            <span class="text-gray-400 italic">inhale... exhale... bringing your data to life...</span>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($submissions)
<div class="mt-6">
    {{ $submissions->links('vendor.pagination.tailwind') }}
</div>
@endif
