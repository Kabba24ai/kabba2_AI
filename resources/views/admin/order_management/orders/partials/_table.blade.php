<div class="shadow rounded-2xl overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="py-4 px-6 text-left">Order ID</th>
                <th class="py-4 px-6 text-left">Customer</th>
                <th class="py-4 px-6 text-left">Company</th>
                <th class="py-4 px-6 text-left">Product</th>
                <th class="py-4 px-6 text-left">Billing Address</th>
                <th class="py-4 px-6 text-left">Phone</th>
                <th class="py-4 px-6 text-right">Amount</th>
                <th class="py-4 px-6 text-center">Payment Type</th>
                <th class="py-4 px-6 text-center">Payment</th>
                <th class="py-4 px-6 text-center">Terms Status</th>
                {{-- <th class="py-4 px-6 text-center">Delivery</th>
                <th class="py-4 px-6 text-center">Return</th> --}}
                <th class="py-4 px-6 text-center">Created</th>
                <th class="py-4 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <div id="order-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orders as $order)
                <tr id="order-row-{{ $order->unique_id }}" class="hover:bg-gray-50">
                    <td class="py-4 px-6">
                        <input type="checkbox" class="order-checkbox" value="{{ $order->unique_id }}" />
                    </td>
                    <td class="py-4 px-6 text-left">
                        {!! $order->view_link !!}
                        <small class="text-gray-500 text-xs block">{{ $order->reference_order_number }}</small>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-medium">{{ $order->customer_name }}</div>
                        {{-- <div class="text-gray-500 text-xs">{{ $order->customer?->unique_id }}</div> --}}
                    </td>
                    <td class="py-4 px-6">
                        {{ $order->company_name }}

                        @if (!empty($order->company_website))
                            <a href="{{ $order->company_website }}" >
                                <div class="text-sm text-brand-500 flex items-center gap-1">
                                    <x-heroicon-o-globe-alt class="w-4 h-4 text-brand-400" />
                                    <span>{{ $order->company_website }}</span>
                                </div>
                            </a>
                        @endif
                    </td>
                    <td class="py-4 px-6 truncate min-w-3xs max-w-3xs text-left">{!! $order->products->pluck('product_name')->join('<br> ') !!}</td>
                    <td class="py-4 px-6 truncate min-w-xs max-w-xs ">{{ $order->billingAddress?->full_address ?? '—' }}</td>
                    <td class="py-4 px-6 text-left ">{{ $order->billingAddress?->phone ?? '—' }}</td>
                    <td class="py-4 px-6 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}
                    </td>
                    <td class="py-4 px-6 text-center">
                        {{ $order?->last_payment_type?->label() ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-center">

                        {!! \App\Helpers\CustomHelper::statusBadge($order->last_payment_status) !!}

                    </td>
                    <td class="py-4 px-6 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($order->terms_status->label() ?? '-') !!}
                    </td>
                    <td class="py-4 px-6 text-center">{{ $order->created_at->format(config('app.date.date_format')) }}
                    </td>
                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                class="text-sky-600 hover:text-sky-800" title="View" >
                                @if ($order->notes_count > 0)
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                            <button class="delete-button text-red-600 hover:text-red-800" title="Delete"
                                data-unique-id="{{ $order->unique_id }}">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-sm text-gray-500 px-4 py-6">
                        @if ($orders)
                            No orders found.
                        @else
                            <span class="text-gray-400 italic">inhale… exhale… bringing your data to life…</span>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{-- Pagination --}}
@if ($orders)
<div class="mt-6">
    {{ $orders->links('vendor.pagination.tailwind') }}
</div>
@endif
