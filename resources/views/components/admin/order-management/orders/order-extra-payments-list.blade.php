@props(['payments'])

<div class="shadow rounded-2xl overflow-x-auto border border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Type</th>
                <th class="py-4 px-6 text-left">Order Product</th>

                <th class="py-4 px-6 text-right">Amount</th>
                <th class="py-4 px-6 text-left">Payment Method</th>
                
                <th class="py-4 px-6 text-left">Recorded By</th>
                <th class="py-4 px-6 text-left">Date</th>
                <th class="py-4 px-6 text-left">Notes</th>
            </tr>
        </thead>

        <tbody class="divide-y">
            @forelse ($payments as $payment)
                <tr class="hover:bg-gray-50">
                    {{-- Type --}}
                    <td class="py-4 px-6 font-medium">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs
                            {{ $payment->type === 'damage'
                                ? 'bg-red-100 text-red-700'
                                : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($payment->type) }}
                        </span>
                    </td>

                       {{-- Amount --}}
                    <td class="py-4 px-6 text-right font-semibold text-gray-900">
                       {{ $payment->orderProduct?->product_name ?? '-' }}
                    </td>


                    {{-- Amount --}}
                    <td class="py-4 px-6 text-right font-semibold text-gray-900">
                        {{ \App\Helpers\CustomHelper::formatCurrency($payment->amount) }}
                    </td>

                    {{-- Payment Method --}}
                    <td class="py-4 px-6">
                        {{ $payment->payment_type }}
                    </td>


                    {{-- Responsible --}}
                    <td class="py-4 px-6">
                        {{ $payment->responsiblePerson?->full_name ?? '—' }}
                    </td>

                    {{-- Date --}}
                    <td class="py-4 px-6 text-gray-500">
                        {{ \App\Helpers\CustomHelper::formatDateTime($payment->created_at) }}
                    </td>

                    {{-- Notes --}}
                    <td class="py-4 px-6 text-gray-600 truncate max-w-xs">
                        {{ $payment->notes ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-6 px-6 text-center text-sm text-gray-400">
                        No extra payments recorded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
