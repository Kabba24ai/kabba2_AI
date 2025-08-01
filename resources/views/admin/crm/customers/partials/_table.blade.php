<div class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white dark:bg-gray-900">

    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left cus-width-3"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="cus-width-20 px-4 py-3 text-left font-semibold">Customer</th>
                <th class="cus-width-20 px-4 py-3 text-left font-semibold">Company</th>
                <th class="cus-width-15 px-4 py-3 text-left font-semibold">Phone</th>
                <th class="cus-width-15 px-4 py-3 text-left font-semibold">Status</th>
                <th class="cus-width-10 px-4 py-3 text-left font-semibold">Orders</th>
                <th class="cus-width-10 px-4 py-3 text-left font-semibold">Total Spent</th>
                <th class="cus-width-10 px-4 py-3 text-left font-semibold">Actions</th>
            </tr>
        </thead>

                                <div id="customer-loader" class="hidden"></div>


        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
            @forelse($customers as $customer)
                <tr id="customer-row-{{ $customer->unique_id }}">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="customer-checkbox"   value="{{ $customer->unique_id }}" />
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $customer->full_name }}</div>
                        <!-- <div class="text-gray-500 text-xs">{{ $customer->unique_id }}</div> -->
                    </td>
                    <td class="px-4 py-3">
                        {{ $customer->company_name }}

                        <a
                            @if (!empty($customer->company_website)) href="{{ $customer->company_website ?? 'javascript:void(0)' }}"  target="_blank" @endif>
                             @if (!empty($customer->company_website))
                            <div class="text-sm text-gray-500 flex items-center gap-1">
                                <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-400" />
                                <span>
                                   
                      {{ preg_replace('#^https?://#', '', $customer->company_website) }}
                                   
                                </span>
                            </div>      
                                    @endif

                        </a>
                    </td>
                    <td class="px-4 py-3  whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            <x-heroicon-o-phone class="h-4 w-4 text-gray-400" />
                            <span> {{ \App\Helpers\CustomHelper::formatPhone($customer->phone ?? '') ?: 'N/A' }}
                            </span>
                        </div>
                        @if(!empty($customer->company_phone))
                        <div class="text-xs text-gray-500">Company:
                            {{ \App\Helpers\CustomHelper::formatPhone($customer->company_phone ?? '') ?: 'N/A' }}
                        </div>
                         @endif
                    </td>
                    @php
                        $status = $customer->customer_account_status ?? 'Good Standing';

                        $statusStyles = [
                            'Good Standing' => [
                                'label' => 'Good Standing',
                                'bg' => 'bg-green-100',
                                'text' => 'text-green-800',
                            ],
                            'Overdue' => ['label' => 'Overdue', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
                            'Bad Debt' => ['label' => 'Bad Debt', 'bg' => 'bg-red-100', 'text' => 'text-red-800'],
                            'Blacklisted' => ['label' => 'Blacklisted', 'bg' => 'bg-gray-800', 'text' => 'text-white'],
                        ];

                        $style = $statusStyles[$status] ?? [
                            'label' => $status,
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-800',
                        ];
                    @endphp




                    <td class="px-4 py-3 inline-flex whitespace-nowrap">
                        <span
                            class="items-center gap-2 px-2 py-1 text-xs font-medium rounded-full {{ $style['bg'] }} {{ $style['text'] }} px-2 py-0.5 text-xs font-medium">
                            {{ $style['label'] }}
                        </span>
                        @if ($customer->tax_status === 'Exempt')
                            <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check
                                    class="w-5 h-5 text-green-500" /></span>
                        @else
                            <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check
                                    class="w-5 h-5 text-gray-500" /></span>
                        @endif


                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span
                            class="inline-block rounded-full bg-blue-100 text-blue-600 px-2 py-0.5 text-xs font-medium">
                            {{ $customer->orders->count() }} orders</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_order_amount) }}
                    </td>
                    <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                        <a href="{{ route('admin.crm.customers.view', $customer->unique_id) }}">

                            <button class="text-blue-600 hover:text-blue-800" title="View">
                                <x-heroicon-o-eye class="w-5 h-5" />
                            </button>

                        </a>


                        <!-- <a href="{{ route('admin.crm.customers.edit', $customer->unique_id) }}">

                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                <x-heroicon-o-pencil class="w-5 h-5" />
                            </button>

                        </a> -->


                        {{-- Delete Button --}}
                        <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}" method="POST"
                            class="inline" onsubmit="return confirm('Are you sure you want to delete this Customer?');">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:text-red-800" title="Delete">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </form>
                        </a>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                        No customers found.
                    </td>
                </tr>
            @endforelse


        </tbody>
    </table>
</div>


{{-- Pagination --}}
<div class="mt-6">
    {{ $customers->links() }}
</div>
