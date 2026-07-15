
<div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm overflow-x-auto mt-6">
  <h2 class="text-lg font-semibold text-gray-900 mb-4">Order History (Read Only)</h2>

  <table class="min-w-full text-sm text-left">
    <thead class="text-gray-500 border-b">
      <tr>
        <th class="px-4 py-2 font-medium uppercase">Order ID</th>
        <th class="px-4 py-2 font-medium uppercase">Product</th>
        <th class="px-4 py-2 font-medium uppercase">Amount</th>
        <th class="px-4 py-2 font-medium uppercase">Payment Method</th>
        <th class="px-4 py-2 font-medium uppercase">Status</th>
        <th class="px-4 py-2 font-medium uppercase">Date</th>
      </tr>
    </thead>
    <tbody>
        <tbody>
            @forelse ($customer->orders as $order)
                <tr class="border-b last:border-0">
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-700"> {{ $customer->full_name }}</td>
                    <td class="px-4 py-3 text-gray-700">

                    {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $order->last_payment_type?->label() ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                'In Progress' => 'bg-blue-100 text-blue-800',
                                'Completed' => 'bg-green-100 text-green-800',
                                'Cancelled' => 'bg-red-100 text-red-800',
                            ];
                            $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-block text-xs font-medium px-3 py-1 rounded-full {{ $statusColor }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">

                        {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                        No order found.
                    </td>
                </tr>
            @endforelse
        </tbody>

    </tbody>
  </table>
</div>
