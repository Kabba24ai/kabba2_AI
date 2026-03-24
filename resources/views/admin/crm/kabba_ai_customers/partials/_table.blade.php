<div class="shadow rounded-2xl overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Unique ID</th>
                <th class="py-4 px-6 text-left">Name</th>
                <th class="py-4 px-6 text-left">Email</th>
                <th class="py-4 px-6 text-left">Phone</th>
                <th class="py-4 px-6 text-left">Business</th>
                <th class="py-4 px-6 text-left">Status</th>
                <th class="py-4 px-6 text-left">Amount</th>
                <th class="py-4 px-6 text-left">Submitted At</th>
            </tr>
        </thead>
        <div id="kabba-ai-customers-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($submissions as $submission)
                <tr class="hover:bg-gray-50">
                    <td class="py-4 px-6">{{ $submission->unique_id ?: '-' }}</td>
                    <td class="py-4 px-6">{{ trim(($submission->first_name ?? '') . ' ' . ($submission->last_name ?? '')) ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->email ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->phone_number ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->business_name ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->status ?: '-' }}</td>
                    <td class="py-4 px-6">{{ $submission->amount ?: '-' }}</td>
                    <td class="py-4 px-6">{{ optional($submission->created_at)->format(config('app.date.date_format') . ' h:i A') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-sm text-gray-500 px-4 py-6">
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
