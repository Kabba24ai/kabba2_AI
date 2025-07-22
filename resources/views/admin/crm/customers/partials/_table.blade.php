
<div class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white dark:bg-gray-900">
    
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="w-45 px-4 py-3 text-left font-semibold w-64">Customer</th>
                <th class="w-40 px-4 py-3 text-left font-semibold w-64">Company</th>
                <th class="w-45 px-4 py-3 text-left font-semibold w-64">Contact</th>
                <th class="w-45 px-4 py-3 text-left font-semibold w-48">Status</th>
                <th class="w-40 px-4 py-3 text-left font-semibold w-24">Orders</th>
                <th class="w-35 px-4 py-3 text-left font-semibold w-24">Total Spent</th>
                <th class="w-35 px-4 py-3 text-left font-semibold w-24">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
            @forelse($customers as $customer)
           
            <tr>
                <td class="px-4 py-3">
                <div class="font-medium">{{$customer->full_name }}</div>
                <div class="text-gray-500 text-xs">{{$customer->unique_id }}</div>
                </td>
                <td class="px-4 py-3">
               {{$customer->company_name }}
               
            <a @if(!empty($customer->company_website)) href="{{ $customer->company_website ?? 'javascript:void(0)' }}"  target="_blank" @endif>
                <div class="text-sm text-gray-500 flex items-center gap-1">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                    <span>@if(!empty($customer->company_website)) {{ $customer->company_website }} @else - @endif</span>
                </div>
                </a>
                </td>
                <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.2 19.8a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.4 12.4 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.4 12.4 0 0 0 2.81.7 2 2 0 0 1 1.81 2z" />
                    </svg>
                    <span>         {{ \App\Helpers\CustomHelper::formatPhone($customer->phone ?? '') ?: 'N/A' }}
                     </span>
                </div>
                <div class="text-xs text-gray-500">Company:         {{ \App\Helpers\CustomHelper::formatPhone($customer->company_phone ?? '') ?: 'N/A' }}
                </div>
                </td>
                @php
                $status = $customer->customer_account_status ?? 'Good Standing';

                $statusStyles = [
                    'Good Standing' => ['label' => 'Good Standing', 'bg' => 'bg-green-100',  'text' => 'text-green-800'],
                    'Overdue'       => ['label' => 'Overdue',       'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
                    'Bad Debt'      => ['label' => 'Bad Debt',      'bg' => 'bg-red-100',    'text' => 'text-red-800'],
                    'Blacklisted'   => ['label' => 'Blacklisted',   'bg' => 'bg-gray-800',   'text' => 'text-white'],
                ];

                $style = $statusStyles[$status] ?? ['label' => $status, 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            @endphp

            


            <td class="px-4 py-3 inline-flex ">
                <span class="items-center gap-2 px-2 py-1 text-xs font-medium rounded-full {{ $style['bg'] }} {{ $style['text'] }} px-2 py-0.5 text-xs font-medium">
                      {{ $style['label'] }}
                </span>
                @if ($customer->tax_status === 'Exempt')
                   <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" /></span>
                @else
                   <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" /></span>
                @endif


            </td>

                <td class="px-4 py-3">
                    <span class="inline-block rounded-full bg-blue-100 text-blue-600 px-2 py-0.5 text-xs font-medium"> {{ $customer->orders->count() }} orders</span>
                </td>
                <td class="px-4 py-3">
                {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_order_amount) }}
                </td>
                <td class="px-4 py-3 space-x-2">
                   <a href="{{ route('admin.crm.customers.view', $customer->unique_id) }}" >

                    <button class="text-blue-600 hover:text-blue-800" title="View">
                        <x-heroicon-o-eye class="w-5 h-5" />
                    </button>

                    </a>


                   <a href="{{ route('admin.crm.customers.edit', $customer->unique_id) }}">
                
                    <button class="text-green-600 hover:text-green-800" title="Edit">
                        <x-heroicon-o-pencil class="w-5 h-5" />
                    </button>

                    </a>
                    {{-- Delete Button --}}
                        <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Are you sure you want to delete this Customer?');">
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