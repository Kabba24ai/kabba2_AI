@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'View Invoice')

@section('content')

@push('css')
<style>
@media print {
  /* allow normal scrolling and page breaking */
  html, body {
    overflow: visible !important;
    height: auto !important;
  }

  /* IMPORTANT: allow the main container to break */
  .min-h-screen.flex {
    display: block !important;
  }

  #printable-area {
    max-width: none !important;   /* remove 3xl constraint */
    width: 100% !important;
    overflow: visible !important;
  }
    #printable-area .grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        /* 50% / 50% */
        gap: 1.5rem !important;
    }
  table, thead, tbody, tr, td, th {
    page-break-inside: auto !important;
  }

  thead {
    display: table-header-group !important; /* repeat headers */
  }

  tfoot {
    display: table-footer-group !important;
  }

  tr {
    page-break-inside: avoid !important;
    page-break-after: auto !important;
  }

  /* remove any overflow:hidden globally */
  * {
    overflow: visible !important;
  }

  /* hide stuff not for print */
  .no-print {display: none !important;}
}


</style>
@endpush

<div class="bg-gray-50 px-4 mb-4 py-4 border-b border-gray-200 no-print">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="{{ route('admin.crm.customers.invoice.index',$customer->unique_id ) }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                </svg>
                <span class="text-sm font-medium">Back to Invoice</span>
            </a>
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Customer Invoice</h1>
                <p class="text-sm text-gray-500">{{ $invoice->customer->full_name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button onclick="closeWindow()" type="button" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
            </button>
        </div>
    </div>
</div>

@include('flash::message')

<div class="min-h-screen  flex flex-col items-center">
    <!-- Header -->
    <div class="text-center mb-6 no-print">
        <h2 class="text-xl font-semibold">Receipt Preview</h2>
        <p class="text-gray-500 text-sm">
            This is how your receipt will look when printed on 8.5" × 11" paper
        </p>
    </div>

    <!-- Receipt Card -->
    <div class="bg-white w-full max-w-3xl shadow-md rounded-md p-6" id="printable-area">
        <!-- Top Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-15 h-15  flex items-center justify-center rounded-lg text-white">
                    <img class="dark:hidden w-20" src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
                </div>
                <div>
                    <h3 class="font-semibold text-2xl">Rent 'n King</h3>
                    <p class="text-sm text-gray-500">Professional Rentals & Sales</p>
                </div>
            </div>
            <div class="text-right mt-4 sm:mt-0">
                <h3 class="text-2xl font-bold">RECEIPT</h3>
                <p class="text-sm text-gray-500">#{{ $invoice->invoice_number }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm border-b-2 border-gray-800 pb-4 mb-6">
            <!-- Left Column -->
            <div class="text-gray-600 space-y-1">
                <p class="font-medium text-gray-700 text-lg">From:</p>
                <p>Rent 'n King .</p>
                <p> 10296 Highway 46</p>
                <p>Bon Aqua, TN 37025</p>
                <p> {{ \App\Helpers\ConfigurationHelper::getSettings(null , 'mobile') }} </p>
                <p>{{ \App\Helpers\ConfigurationHelper::getSettings(null , 'email') }} </p>
            </div>
            <!-- Right Column -->
            <div class="space-y-3">
                <div>
                    <p class="flex items-center gap-2 font-semibold text-gray-900 ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 text-gray-600">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                            <path d="M3 10h18"></path>
                        </svg>
                        Transaction Date:
                    </p>
                    <p class="text-lg font-medium text-gray-900"> {{ \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? null) }} </p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Customer PO:</p>
                    <p class="text-lg font-medium text-gray-900">{{ $invoice->customer->unique_id }}</p>
                </div>
            </div>
        </div>

        <!-- Bill To Label -->
        <div>
            <p class="font-medium mb-2 flex items-center space-x-2 text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-5 h-5">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Bill To:</span>
            </p>
        </div>
        <!-- Customer Card -->
        <div class=" p-4">
            <p class="font-medium text-lg text-gray-900">{{ $invoice->customer->full_name }}</p>
            <p class="text-gray-700 font-medium">{{ $invoice->customer->company_name }}</p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-gray-700">
                <!-- Phone -->
                <div class="flex text-md items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>{{ \App\Helpers\CustomHelper::formatPhone($invoice->customer->phone ?? '') ?: 'N/A' }} </span>
                </div>

                @php
                $billingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Billing' );
                $shippingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Shipping');

                $defaultAddresses = [
                ['label' => 'Billing', 'data' => $billingAddress],
                ['label' => 'Shipping', 'data' => $shippingAddress],
                ];
                @endphp

                <!-- Email -->
                <div class="flex items-start space-x-2 ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail w-4 h-4">
                        <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>
                    <span>{{ $invoice->customer->email ?? 'N/A' }}</span>
                </div>

                <!-- Address -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between sm:col-span-2 gap-6">
                    @foreach ($defaultAddresses as $index => $addressItem)
                    @php $addresse = $addressItem['data']; @endphp
                    <div class="flex items-start space-x-2 flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-4 h-4 mt-1">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>
                            <p class="font-medium">{{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address</p>
                            @if($addresse?->full_name)
                            {{ $addresse->full_name }}
                            @endif

                            @if($addresse?->address || $addresse?->city)
                            {{ $addresse->address ?? '' }}{{ $addresse?->city ? ', ' . $addresse->city : '' }}
                            @endif

                            @if($addresse?->state?->name || $addresse?->zip_code)
                            {{ $addresse?->state?->name ?? '' }}{{ $addresse?->zip_code ? ' ' . $addresse->zip_code : '' }}
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <!-- Table Head -->
                <thead>
                    <tr class="border-b border-gray-300 mb-4">
                        <th class="text-left py-2 font-semibold text-gray-700 whitespace-nowrap">Item</th>
                        <th class="text-center py-2 font-semibold text-gray-700 whitespace-nowrap">Qty</th>
                        <th class="text-right py-2 font-semibold text-gray-700 whitespace-nowrap">Unit Price</th>
                        <th class="text-right py-2 font-semibold text-gray-700 whitespace-nowrap">Total</th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody>

                    @foreach($invoice->items as $item)
                    {{-- Main Item Row --}}
                    <tr>
                        <td class="pt-2 pb-2 align-top whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ $item->item_name }}</div>
                        </td>
                        <td class="pt-2 text-center align-top whitespace-nowrap">
                            @if ($item->type === 'order')
                            {{ $item->orderProduct->quantity ?? 1 }}
                            @else
                            {{ $item->qty }}
                            @endif
                        </td>
                        <td class="pt-2 text-right align-top whitespace-nowrap">

                            @if ($item->type === 'order')
                            {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->price) }}
                            @else
                            {{ \App\Helpers\CustomHelper::formatCurrency($item->unit) }}
                            @endif

                        </td>
                        <td class="pt-2 text-right align-top font-semibold whitespace-nowrap">


                            @if ($item->type === 'order')
                            @php

                            $rowprice = $item->orderProduct->price ;

                            $qty = $item->orderProduct->quantity ?? 1 ;

                            $total = $rowprice * $qty ;

                            @endphp

                            @else

                            @php

                            $rowprice = $item->unit ;

                            $qty = $item->qty ?? 1 ;

                            $total = $rowprice * $qty ;

                            @endphp


                            @endif

                            {{ \App\Helpers\CustomHelper::formatCurrency($total) }}

                        </td>
                    </tr>

                    {{-- If item is ORDER TYPE → show product_data rows --}}
                    @if($item->type === 'order' && $item->orderProduct && $item->orderProduct->product_data)
                    {{-- Rental Items --}}
                    @foreach ($item->orderProduct->product_data['product_rental_items_prices'] ?? [] as $rentalKey => $rentalPrice)
                    <tr>
                        <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">
                            +
                            @php
                            $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())
                            ->firstWhere('name', $rentalKey)?->value;
                            $quantity = $case ? $item->orderProduct->quantity : 1;
                            @endphp
                            {{ $case ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                            <span class="text-xs text-gray-400">(x{{ $quantity }})</span>
                        </td>
                        <td></td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                        </td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice * $quantity) }}
                        </td>
                    </tr>
                    @endforeach

                    {{-- Option Items --}}
                    @foreach ($item->orderProduct->product_data['product_option_items'] ?? [] as $option)
                    <tr>
                        <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">
                            + {{ $option['name'] }}
                            <span class="text-xs text-gray-400">(x{{ $item->orderProduct->quantity ?? 1 }})</span>
                        </td>
                        <td></td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                        </td>
                        <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                            {{ \App\Helpers\CustomHelper::formatCurrency(($option['price'] ?? 0) * ($item->orderProduct->quantity ?? 1)) }}
                        </td>
                    </tr>
                    @endforeach

                    @endif

                    <tr class="border-b"></tr>

                    @endforeach

                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="mt-6 text-sm">
            <div class="flex justify-between py-1">
                <span class="text-gray-700 text-md">Subtotal:</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($invoice->subtotal) }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-gray-700">Tax ( {{ $sales_tax }} %):</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($invoice->sales_tax) }}</span>
            </div>
            <div class="flex justify-between border-t mt-2 pt-2 font-bold text-lg">
                <span>Total:</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($invoice->total) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500 border-t border-gray-800">
            <p class="mt-6">Thank you for your business!</p>
            <p>For questions about this receipt, contact us at {{ \App\Helpers\ConfigurationHelper::getSettings(null , 'mobile') }}</p>
        </div>
        <!-- Buttons -->
        <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3 no-print">
            {{-- Print Receipt --}}
            <a href="javascript:void(0);" onclick="window.print();"
                class="bg-blue-600 hover:bg-blue-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Receipt
            </a>
            <a href="{{ route('admin.crm.customers.invoice.sendemail', $invoice->unique_id) }}" class="bg-green-600 hover:bg-green-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Email Receipt
            </a>
            {{-- Download PDF --}}
            <a href="{{ route('admin.crm.customers.invoice.download',$invoice->unique_id ) }}"
                class="bg-purple-600 hover:bg-purple-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>
    </div>
</div>

@endsection


@push('js')
<script>
    function closeWindow() {
        // Attempt to close the current window
        window.close();

        // Fallback: if window.close() is blocked, redirect to about:blank
        setTimeout(() => {
            if (!window.closed) {
                window.location.href = 'about:blank';
            }
        }, 100);
    }
</script>

@endpush
