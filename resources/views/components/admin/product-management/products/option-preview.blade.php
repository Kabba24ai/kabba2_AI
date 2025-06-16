<div>
    <div class="mb-6 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <!-- Header with Option Name -->
        <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">
            {{ $objProductOption->name ?? 'Option Preview' }}
        </h4>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr
                        class="bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-300 uppercase text-left">
                        <th class="px-3 py-2 text-start">#</th>
                        <th class="px-3 py-2 text-start">Label</th>

                        @if ($objProductOption->type === 'Rental')
                            <th class="px-3 py-2 text-end">Daily</th>
                            <th class="px-3 py-2 text-end">W/E Spcl.</th>
                            <th class="px-3 py-2 text-end">Weekly</th>
                            <th class="px-3 py-2 text-end">Monthly</th>
                        @elseif($objProductOption->type === 'Retail')
                            <th class="px-3 py-2 text-end">Retail Price</th>
                        @endif

                        <th class="px-3 py-2 text-center">Charged Per Order</th>
                        <th class="px-3 py-2 text-center">Value</th>
                        <th class="px-3 py-2 text-center">Comment</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @forelse ($objProductOption->items as $item)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-3 py-2 text-start">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 text-start">{{ $item->label }}</td>

                            @if ($objProductOption->type === 'Rental')
                                <td class="px-3 py-2 text-end">{{ $item->daily }}</td>
                                <td class="px-3 py-2 text-end">{{ $item->weekend }}</td>
                                <td class="px-3 py-2 text-end">{{ $item->weekly }}</td>
                                <td class="px-3 py-2 text-end">{{ $item->monthly }}</td>
                            @elseif($objProductOption->type === 'Retail')
                                <td class="px-3 py-2 text-end">{{ $item->retail_price }}</td>
                            @endif

                            <td class="px-3 py-2 text-center">{{ $item->charged }}</td>
                            <td class="px-3 py-2 text-center">{{ $item->value }}</td>

                            <td class="px-3 py-2 text-center">
                                @php
                                    $hasComment = !empty($item->comment);
                                @endphp

                                <button type="button"
                                    class="comment-btn transition text-sm px-2 py-1 rounded
            {{ $hasComment ? 'text-blue-600 hover:text-blue-800 cursor-pointer' : 'text-gray-400 cursor-not-allowed' }}"
                                    {{ $hasComment ? '' : 'disabled' }} data-index="{{ $loop->index }}"
                                    data-comment="{{ $item->comment }}" data-accept="{{ $item->accept_label }}"
                                    data-decline="{{ $item->decline_label }}" data-label="{{ $item->label }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H6l-4 4V5z" />
                                    </svg>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $objProductOption->type === 'Rental' ? 9 : 6 }}"
                                class="px-3 py-2 text-center text-gray-500 dark:text-gray-400">
                                No options available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
