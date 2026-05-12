<div class="overflow-x-auto rounded-xl shadow border border-gray-200 bg-white dark:bg-gray-900">

    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">  
            <tr>
                <th class="py-4 px-6 text-left cus-width-3"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="cus-width-20 py-4 px-6 text-left font-semibold">Customer</th>
                <th class="cus-width-20 py-4 px-6 text-left font-semibold">Company</th>
                <th class="cus-width-15 py-4 px-6 text-left font-semibold">Phone</th>

                <th class="cus-width-15 py-4 px-6 text-left font-semibold whitespace-nowrap">
                    Tags
                </th>

                <th class="cus-width-15 py-4 px-6 text-left font-semibold whitespace-nowrap">
                    Sales Funnels
                </th>

                <th class="cus-width-15 py-4 px-6 text-left font-semibold">Status</th>
                <th class="cus-width-10 py-4 px-6 text-left font-semibold">Orders</th>
                <th class="cus-width-10 py-4 px-6 text-left font-semibold whitespace-nowrap">Total Spent</th>
                <th class="cus-width-10 py-4 px-6 text-left font-semibold">Actions</th>
            </tr>
        </thead>

        <div id="customer-loader" class="hidden"></div>


        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
            @forelse($customers as $customer)
            <tr id="customer-row-{{ $customer->unique_id }}">
                <td class="py-4 px-6">
                    <input type="checkbox" class="customer-checkbox" value="{{ $customer->unique_id }}" />
                </td>
                <td class="py-4 px-6">
                    <div class="font-medium">{{ $customer->full_name }}</div>
                    <!-- <div class="text-gray-500 text-xs">{{ $customer->unique_id }}</div> -->
                </td>
                <td class="py-4 px-6">
                    {{ $customer->company_name }}

                    <a
                        @if (!empty($customer->company_website)) href="{{ $customer->company_website ?? 'javascript:void(0)' }}" @endif>
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
                <td class="py-4 px-6 whitespace-nowrap">
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
                    $account = \App\Helpers\CustomHelper::getCustomerAccountStatus($customer);
                        $isSuspended = $customer->status === 'Archived' || $customer->status === 'Suspended';

                @endphp

        
                {{-- Tags --}}
                <td class="py-4 px-6">

                    @php
                        $tags = $customer->tag_objects ?? collect();

                        $visibleTags = $tags->take(5);

                        $remainingTags = $tags->slice(5);
                    @endphp

                    <div class="flex flex-wrap gap-1 items-center">

                        @forelse($visibleTags as $tag)

                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">

                                {{ $tag->name }}

                            </span>

                        @empty

                            <span class="text-xs text-gray-400">
                                No Tags
                            </span>

                        @endforelse

                        {{-- More Tags --}}
                        @if($remainingTags->count() > 0)

                            <div class="relative group">

                                <button
                                    type="button"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">

                                    +{{ $remainingTags->count() }} more

                                </button>

                                {{-- Hover Popup --}}
                                <div
                                    class="absolute left-0 top-full mt-2 hidden group-hover:block z-50 w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-3">

                                    <div class="flex flex-wrap gap-2">

                                        @foreach($remainingTags as $tag)

                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">

                                                {{ $tag->name }}

                                            </span>

                                        @endforeach

                                    </div>

                                </div>

                            </div>

                        @endif

                    </div>

                </td>

                <td class="py-4 px-6 whitespace-nowrap">

                    {{-- @if($customer->latestInvoice)

                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">

                            {{ ucfirst($customer->latestInvoice->invoice_status) }}

                        </span>

                    @else --}}

                        <span class="text-xs text-gray-400">
                            No Funnel
                        </span>

                    {{-- @endif --}}

                </td>



                <td class="py-4 px-6 inline-flex whitespace-nowrap">
                  
                    @if($isSuspended)
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-600 flex items-center">
                            Suspended
                        </span>
                    @else
                    <!-- Status Badge -->
                    <span class="items-center gap-2 px-2 py-1 text-xs font-medium rounded-full flex items-center  px-2 py-0.5 text-xs font-medium
                        {{ $account['badge']['bg'] }} {{ $account['badge']['text'] }}">
                        {{ $account['badge']['label'] }}
                    </span>
                    @endif

                    @if ($customer->tax_status === 'Exempt')
                    <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check
                            class="w-5 h-5 text-green-500" /></span>
                    @else
                    <span class="items-center gap-2 px-2 py-1"> <x-heroicon-o-shield-check
                            class="w-5 h-5 text-gray-500" /></span>
                    @endif


                          <!-- Payment Alert Icon -->
                <!-- @if ($account['alert']['show'])
                    <span class="items-center gap-2  py-1">
                        <x-heroicon-o-currency-dollar
                            title="No payment for {{ $account['alert']['days'] }} days"
                            class="w-5 h-5
                                {{ $account['alert']['color'] === 'yellow' ? 'text-yellow-500' : '' }}
                                {{ $account['alert']['color'] === 'orange' ? 'text-orange-500' : '' }}
                                {{ $account['alert']['color'] === 'red' ? 'text-red-600' : '' }}
                            " />
                    </span>
                    @endif -->


                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <span
                        class="inline-block rounded-full bg-blue-100 text-blue-600 px-2 py-0.5 text-xs font-medium">
                        {{ $customer->orders->count() }} orders</span>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                    {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_order_amount) }}
                </td>
                <td class="py-4 px-6 space-x-2 whitespace-nowrap">
                    <a href="{{ route('admin.crm.customers.view', $customer->unique_id) }}">

                        <button class="text-blue-600 hover:text-blue-800" title="View">
                            <x-heroicon-o-eye class="w-5 h-5" />
                        </button>

                    </a>

                    <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}"
                        method="POST"
                        class="inline delete-customer-form"
                        data-customer-name="{{ $customer->full_name }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                            <x-heroicon-o-trash class="w-5 h-5" />
                        </button>
                    </form>

                   

                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                    @if ($customers)
                        No customers found.
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
@if ($customers)
<div class="mt-6">
    {{ $customers->links() }}
</div>
@endif


@push('js')



<!-- delete- -->
<!-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-customer-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // stop auto submit

                const templateName = form.getAttribute('data-customer-name') || 'this customer';

                window.showConfirm(
                    `Delete "${templateName}"? This action cannot be undone!`,
                    'Delete customer'
                ).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script> -->

<!-- delete- -->


<script>
document.addEventListener('submit', function (e) {
  const form = e.target.closest('.delete-customer-form');
  if (!form) return;

  e.preventDefault();

  const name = form.dataset.customerName || 'this customer';

  window.showConfirm(
    `Delete "${name}"? This action cannot be undone!`,
    'Delete customer'
  ).then(result => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
});
</script>



@endpush
