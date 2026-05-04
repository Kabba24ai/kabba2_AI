@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'View Invoice')

@section('content')

@push('css')
<style>
    @media print {

        /* allow normal scrolling and page breaking */
        html,
        body {
            overflow: visible !important;
            height: auto !important;
        }

        /* IMPORTANT: allow the main container to break */
        .min-h-screen.flex {
            display: block !important;
        }

        #printable-area {
            max-width: none !important;
            /* remove 3xl constraint */
            width: 100% !important;
            overflow: visible !important;
        }

        #printable-area .grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            /* 50% / 50% */
            gap: 1.5rem !important;
        }

        table,
        thead,
        tbody,
        tr,
        td,
        th {
            page-break-inside: auto !important;
        }

        thead {
            display: table-header-group !important;
            /* repeat headers */
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
        .no-print {
            display: none !important;
        }
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
      <h2 class="text-xl font-semibold">Invoice Preview</h2>
      <p class="text-black-500 text-sm">
         This is how your invoice will look when printed on 8.5" × 11" paper
      </p>
   </div>
   
                  @php
                    $siteLogo = \App\Helpers\ConfigurationHelper::getBrandingLogo();
                    $sitename = \App\Helpers\ConfigurationHelper::getSettings('Website Management Branding','site_name');
                    $primaryStore = \App\Models\Stores\Store::primary()->first();
                    @endphp

   <!-- Invoice Card -->
   <div class="bg-white w-full max-w-3xl shadow-md rounded-md p-6" id="printable-area">
      <!-- Top Section -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
         <div class="flex items-center space-x-3">
            <div class="w-15 h-15  flex items-center justify-center rounded-lg text-white">
               <img class="dark:hidden w-20" src="{{ $siteLogo }}" alt="Logo" />
            </div>
            <div>
               <h3 class="font-semibold text-2xl">{{ $sitename ?: "Rent 'n King" }}</h3>
               <p class="text-sm text-black-500">Professional Rentals & Sales</p>
            </div>
         </div>
         <div class="text-right mt-4 sm:mt-0">
            <h3 class="text-2xl font-bold">Invoice</h3>
            <p class="text-sm text-black-500">#{{ $invoice->invoice_number }}</p>
         </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm border-b-2 border-gray-800 pb-4 mb-6">
         <!-- Left Column -->
         <div class="text-black-600 space-y-1">
            <p class="font-medium text-black-700 text-lg">From:</p>
           {{ $sitename ?: "Rent 'n King" }} <br>

                                               @if($primaryStore)
                                                    {{ $primaryStore->address }}, <br>
                                                    {{ $primaryStore->city }},
                                                    {{ optional($primaryStore->state)->name }},
                                                    {{ $primaryStore->zip_code }}. <br>
                                                @endif
            @php
            $invoicePhone = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_phone');
            $invoiceEmail = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_email');
            @endphp
            @if(!empty($invoicePhone))
            {{ $invoicePhone }} <br>
            @endif
            @if(!empty($invoiceEmail))
            {{ $invoiceEmail }}
            @endif
         </div>
         <!-- Right Column -->
         <div class="space-y-3">
            <div>
               <p class="flex items-center gap-2 font-semibold text-black-900 ">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 text-gray-600">
                     <path d="M8 2v4"></path>
                     <path d="M16 2v4"></path>
                     <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                     <path d="M3 10h18"></path>
                  </svg>
                  Invoice Date :- {{ \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? null) }}
               </p>
            </div>
            <div>
               <p class="flex items-center gap-2 font-semibold text-black-900 ">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 text-gray-600">
                     <path d="M8 2v4"></path>
                     <path d="M16 2v4"></path>
                     <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                     <path d="M3 10h18"></path>
                  </svg>
                  Due Date :- {{ $invoice->due_date ? \App\Helpers\CustomHelper::formatDate($invoice->due_date) : 'Pay Upon Invoice' }}
               </p>
            </div>
            <div class="flex gap-2">
               {{-- 
               <p class="font-semibold text-gray-900">Account:</p>
               <p class=" font-medium text-gray-600">{{ $invoice->customer->unique_id }}</p>
               --}}
            </div>
         </div>
      </div>
      <!-- Bill To -->
      <div class="flex items-center gap-2 text-black-800">
         <svg xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5 text-gray-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
         </svg>
         <span class="font-semibold">Bill To:</span>
      </div>
      <!-- 50 / 50 Name + Customer ID -->
      <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
         <!-- LEFT 50% -->
         <div class="text-lg font-semibold text-black-700">
            {{ $invoice->customer->full_name }}
            @if($invoice->customer->company_name)
            <span class="text-sm font-normal text-black-600">
            ({{ $invoice->customer->company_name }})
            </span>
            @endif
         </div>
         <!-- RIGHT 50% -->
         <div class="text-sm text-black-700 sm:text-left flex items-end">
            <span class="font-semibold">Account ID:</span>
            {{ $invoice->customer->unique_id ?? 'N/A' }}
         </div>
      </div>
      <!-- Customer Card -->
      <div class="pt-2 pb-2">
         <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-black-700">
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
               @php
               $billing = collect($defaultAddresses)->firstWhere('label', 'Billing')['data'] ?? null;
               $shipping = collect($defaultAddresses)->firstWhere('label', 'Shipping')['data'] ?? null;
               $isSameAddress = $billing && $shipping &&
               $billing->address === $shipping->address &&
               $billing->city === $shipping->city &&
               $billing->state_id === $shipping->state_id &&
               $billing->zip_code === $shipping->zip_code;
               @endphp
               @foreach ($defaultAddresses as $addressItem)
               @php $addresse = $addressItem['data']; @endphp
               <div class="flex items-start space-x-2 flex-1">
                  <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-4 h-4 mt-1"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">
                     <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                     <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>
                     <p class="font-medium">
                        {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                     </p>
                     <div class="leading-relaxed">
                        {{-- If Shipping and same as Billing --}}
                        @if($addressItem['label'] === 'Shipping' && $isSameAddress)
                        <div>Same as Billing Address</div>
                        @else
                        @if($addresse?->address)
                        <div>{{ $addresse->address }},  @if($addresse?->city)
                           {{ $addresse->city }},
                           @endif 
                        </div>
                        @endif
                        <div>
                           @if($addresse?->state?->name)
                           {{ $addresse->state->name }} , 
                           @endif
                           @if($addresse?->zip_code)
                           {{ ' ' . $addresse->zip_code }}
                           @endif
                           @if($addresse?->country)
                           {{ ', ' . $addresse->country }}
                           @endif
                        </div>
                        @endif
                     </div>
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
                  <th class="text-left py-2 font-semibold text-black-700 whitespace-nowrap">ID#</th>
                  <th class="text-left py-2 font-semibold text-black-700 whitespace-nowrap">Item</th>
                  <th class="text-left py-2 font-semibold text-black-700 whitespace-nowrap">PO#</th>
                  {{-- 
                  <th class="text-center py-2 font-semibold text-black-700 whitespace-nowrap">Qty</th>
                  --}}
                  <th class="text-right py-2 font-semibold text-black-700 whitespace-nowrap">Price</th>
                  <th class="text-right py-2 font-semibold text-black-700 whitespace-nowrap">Tax</th>
                  <th class="text-right py-2 font-semibold text-black-700 whitespace-nowrap">Total</th>
               </tr>
            </thead>
            <!-- Table Body -->
            <tbody>
               @foreach($invoice->items as $item)
               {{-- Main Item Row --}}
               <tr>
                  <td class="pt-2 pb-2 align-top whitespace-nowrap">
                     @if ($item->type === 'order')
                     {!! $item->orderProduct->order->view_link !!}
                     @else
                     -
                     @endif
                  </td>
                  <td class="pt-2 pb-2 align-top whitespace-nowrap">
                     <div class="font-medium text-black-900">{{ $item->item_name }}</div>
                     <div class="text-black-500 text-xs"> {{  $item->notes ?? ''}} </div>
                  </td>
                  <td class="pt-2 pb-2 align-top whitespace-nowrap">
                     @if ($item->type === 'order')
                     {{  $item->orderProduct->order->po_id ?? '-'}}
                     @else
                     -
                     @endif
                  </td>
                  {{-- 
                  <td class="pt-2 text-center align-top whitespace-nowrap">
                     @if ($item->type === 'order')
                     {{ $item->orderProduct->quantity ?? 1 }}
                     @else
                     {{ $item->qty }}
                     @endif
                  </td>
                  --}}
                  <td class="pt-2 text-right align-top whitespace-nowrap">
                     @php
                     $isAdjustment = in_array($item->type, ['discount', 'refund']);
                     $textColor = $isAdjustment ? 'text-green-600' : '';
                     $prefix = $isAdjustment ? '-' : '';
                     @endphp
                     <span class="{{ $textColor }}">
                     @if ($item->type === 'order')
                     {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->sub_total) }}
                     @else
                     {{ $prefix }}  {{ \App\Helpers\CustomHelper::formatCurrency($item->unit) }}
                     @endif
                     </span>
                  </td>
                  <td class="pt-2 text-right align-top whitespace-nowrap">
                     @php
                     $isAdjustment = in_array($item->type, ['discount', 'refund']);
                     $textColor = $isAdjustment ? 'text-green-600' : '';
                     $prefix = $isAdjustment ? '-' : '';
                     @endphp
                     <span class="{{ $textColor }}">
                     {{ $prefix }}{{ \App\Helpers\CustomHelper::formatCurrency($item->tax) }}
                     </span>
                  </td>
                  <td class="pt-2 text-right align-top font-semibold whitespace-nowrap">
                     @php
                     if ($item->type === 'order') {
                     $rowprice = $item->orderProduct->price ;
                     $qty = $item->orderProduct->quantity ?? 1;
                     $total_without_tax = $rowprice * $qty;
                     $total = $item->orderProduct->sub_total + $item->orderProduct->tax;
                     } else {
                     $rowprice = $item->total;
                     $qty = $item->qty ?? 1;
                     $total = $rowprice * $qty;
                     }
                     $isAdjustment = in_array($item->type, ['discount', 'refund']);
                     $textColor = $isAdjustment ? 'text-green-600' : '';
                     $prefix = $isAdjustment ? '-' : '';
                     @endphp
                     <span class="{{ $textColor }}">
                     {{ $prefix }}{{ \App\Helpers\CustomHelper::formatCurrency($total) }}
                     </span>
                  </td>
               </tr>
               {{-- If item is ORDER TYPE → show product_data rows --}}
               {{-- @if($item->type === 'order' && $item->orderProduct && $item->orderProduct->product_data) --}}
               {{-- @foreach ($item->orderProduct->product_data['product_rental_items_prices'] ?? [] as $rentalKey => $rentalPrice) --}}
               {{-- 
               <tr >
                  <td></td>
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
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                  </td>
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice * $quantity) }}
                  </td>
               </tr>
               --}}
               {{-- @endforeach --}}
               {{-- distance_range  --}}
               {{-- @if (!empty($item->orderProduct->distance_range)) --}}
               {{-- 
               <tr>
                  <td></td>
                  <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">
                     + Distance Range
                     <span class="text-xs text-gray-400">( {{ ucfirst($item->orderProduct->distance_type) }}
                     ({{ ucfirst($item->orderProduct->distance_range) }}))</span>
                  </td>
                  <td></td>
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                  </td>
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                  </td>
               </tr>
               --}}
               {{-- @endif  --}}
               {{-- Option Items --}}
               {{-- @foreach ($item->orderProduct->product_data['product_option_items'] ?? [] as $option)
               <tr>
                  <td></td>
                  <td class="pl-6 pb-2 text-gray-500 text-sm whitespace-nowrap">
                     + {{ $option['name'] }}
                     <span class="text-xs text-gray-400">(x{{ $item->orderProduct->quantity ?? 1 }})</span>
                  </td>
                  <td></td>
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                  </td>
                  <td></td>
                  <td class="text-right pb-2 text-gray-500 text-sm whitespace-nowrap">
                     {{ \App\Helpers\CustomHelper::formatCurrency(($option['price'] ?? 0) * ($item->orderProduct->quantity ?? 1)) }}
                  </td>
               </tr>
               @endforeach --}}
               {{-- @endif --}}
               <tr class="border-b"></tr>
               @endforeach
            </tbody>
         </table>
      </div>
      @php
      $discountTotal = 0;
      $refundTotal = 0;
      foreach ($invoice->items as $item) {
      if (in_array($item->type, ['discount', 'refund'])) {
      $amount = ($item->unit ?? 0) * ($item->qty ?? 1);
      if ($item->type === 'discount') {
      $discountTotal += $amount;
      }
      if ($item->type === 'refund') {
      $refundTotal += $amount;
      }
      }
      }
      $adjustmentsTotal = $discountTotal + $refundTotal;
      $finalTotal = $invoice->total - $adjustmentsTotal;
      @endphp
      <!-- Totals Section -->
      <div class="mt-8 text-sm max-w-md ml-auto space-y-2">
         <!-- Subtotal -->
         <div class="grid grid-cols-4">
            <div></div>
            <div></div>
            <div class="text-right text-black-700">Subtotal:</div>
            <div class="text-right ">
               {{ \App\Helpers\CustomHelper::formatCurrency($invoice->subtotal) }}
            </div>
         </div>
         <!-- Tax -->
         <div class="grid grid-cols-4">
            <div></div>
            <div></div>
            <div class="text-right text-black-700">
               Tax ({{ \App\Helpers\CustomHelper::displayPercentage($sales_tax) }}%):
            </div>
            <div class="text-right ">
               {{ \App\Helpers\CustomHelper::formatCurrency($invoice->sales_tax) }}
            </div>
         </div>
         @if($discountTotal > 0)
         <div class="grid grid-cols-4 text-green-600">
            <div></div>
            <div></div>
            <div class="text-right">Discount:</div>
            <div class="text-right ">
               -{{ \App\Helpers\CustomHelper::formatCurrency($discountTotal) }}
            </div>
         </div>
         @endif
         @if($refundTotal > 0)
         <div class="grid grid-cols-4 text-green-600">
            <div></div>
            <div></div>
            <div class="text-right">Refund:</div>
            <div class="text-right">
               -{{ \App\Helpers\CustomHelper::formatCurrency($refundTotal) }}
            </div>
         </div>
         @endif
         <!-- Divider -->
         {{-- 
         <div class=" my-3"></div>
         --}}
         <!-- Total -->
         <div class=" grid grid-cols-4 font-semibold text-lg">
            <div></div>
            <div></div>
            <div class="border-t border-gray-300 text-right">Total:</div>
            <div class="text-right border-t border-gray-300 ">
               {{ \App\Helpers\CustomHelper::formatCurrency($invoice->total) }}
            </div>
         </div>
      </div>
      <!-- Footer -->
      <div class="mt-3 text-center text-sm text-black-500 border-t border-gray-800">
         @if(!empty($invoice->invoice_notes))
         <p class="mt-6">
            {{ $invoice->invoice_notes }}
         </p>
         @else
         <p class="mt-5"></p>
         @endif
         <p style="margin:0;">Thank you for your business!</p>
         <p style="margin:0;">*Order details can be found In your Customer Portal</p>
         @php
         $supportPhone = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_phone');
         @endphp
         @if(!empty($supportPhone))
         <p style="margin:0;">
            For questions about this Invoice, contact us at {{ $supportPhone }}
         </p>
         @endif
      </div>
      <!-- Buttons -->
      <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3 no-print">
         {{-- Print Invoice --}}
         <a href="javascript:void(0);" onclick="window.print();"
            class="bg-blue-600 hover:bg-blue-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Invoice
         </a>
         <a href="javascript:void(0);"   data-invoice-id="{{ $invoice->unique_id }}"
            data-billing-email="{{ optional($invoice->customer->billingAddress)->email }}"
            data-customer-email="{{ optional($invoice->customer)->email }}" class="send-invoice-email bg-green-600 hover:bg-green-700 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email Invoice
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
         <!-- bottom checkbox -->
            <button
            id="mark-mail-sent"
            data-invoice-id="{{ $invoice->unique_id }}"
            {{ $invoice->is_mail === 'yes' ? 'disabled' : '' }}
            class="bg-gray-700 hover:bg-gray-800 disabled:bg-gray-400 text-white gap-2 text-sm px-4 py-2 rounded-lg font-medium flex items-center justify-center space-x-2">
            @if($invoice->is_mail === 'yes' && $invoice->is_mail_date)
            Mail Sent: {{ App\Helpers\CustomHelper::formatDate($invoice->is_mail_date) }}
            @else
            Mark Mail Sent
            @endif
            </button>
    </div>
   </div>
</div>
<div id="sendInvoiceModalWrapper"
   style="display: none;"
   class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
   <div class="modal-scrollable w-full mx-auto">
      <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
         <!-- Header -->
         <div class="flex justify-between items-center px-6 pt-4">
            <div class="flex items-center gap-2">
               <div class="text-purple-600">
                  <x-heroicon-o-envelope class="w-5 h-5"/>
               </div>
               <h2 class="text-lg font-medium text-gray-900">
                  Send Invoice
               </h2>
            </div>
            <button id="closeSendInvoiceModalBtn"
               class="text-gray-400 hover:text-gray-700 text-xl">
            &times;
            </button>
         </div>
         <!-- Body -->
         <div class="px-6 overflow-y-auto">
            <form id="sendInvoiceForm"
               method="POST"
               action="{{ route('admin.crm.customers.invoice.sendemail') }}"
               class="space-y-6">
               @csrf
               <input type="hidden" id="invoice_id" name="invoice_id">
               <!-- Billing Email -->
               <div class="flex items-center gap-3">
                  <input type="checkbox"
                     id="billing_email_checkbox"
                     name="send_billing"
                     value="1"
                     class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                     checked>
                  <label for="billing_email_checkbox"
                     class="text-sm text-gray-700">
                  Billing Email:
                  <span id="billing_email_text"
                     class="font-medium text-gray-900"></span>
                  </label>
               </div>
               <!-- Customer Email -->
               <div class="flex items-center gap-3">
                  <input type="checkbox"
                     id="customer_email_checkbox"
                     name="send_customer"
                     value="1"
                     class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                     checked>
                  <label for="customer_email_checkbox"
                     class="text-sm text-gray-700">
                  Customer Email:
                  <span id="customer_email_text"
                     class="font-medium text-gray-900"></span>
                  </label>
               </div>
               <!-- Buttons -->
               <div class="flex gap-2 pb-4">
                  <button type="button"
                     id="cancelSendInvoiceBtn"
                     class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                  Cancel
                  </button>
                  <button type="submit"
                     id="submitSendInvoiceBtn"
                     class="relative flex-1 px-4 py-2 text-sm rounded bg-purple-600 text-white flex items-center justify-center gap-2">
                     <span id="sendInvoiceBtnText">Send Invoice</span>
                     <svg id="sendInvoiceBtnSpinner"
                        xmlns="http://www.w3.org/2000/svg"
                        class="hidden animate-spin h-5 w-5 text-white"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25"
                           cx="12" cy="12" r="10"
                           stroke="currentColor"
                           stroke-width="4"></circle>
                        <path class="opacity-75"
                           fill="currentColor"
                           d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                     </svg>
                  </button>
               </div>
            </form>
         </div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('sendInvoiceModalWrapper');
    const closeBtn = document.getElementById('closeSendInvoiceModalBtn');
    const cancelBtn = document.getElementById('cancelSendInvoiceBtn');
    const form = document.getElementById('sendInvoiceForm');

    const billingCheckbox = document.getElementById('billing_email_checkbox');
    const customerCheckbox = document.getElementById('customer_email_checkbox');
    const billingText = document.getElementById('billing_email_text');
    const customerText = document.getElementById('customer_email_text');

    document.querySelectorAll('.send-invoice-email').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const invoiceId = this.dataset.invoiceId;
            const billingEmail = this.dataset.billingEmail;
            const customerEmail = this.dataset.customerEmail;

            document.getElementById('invoice_id').value = invoiceId;

            // Set email text
            billingText.innerText = billingEmail ?? 'Not available';
            customerText.innerText = customerEmail ?? 'Not available';

            // Billing email handling
            if (billingEmail) {
                billingCheckbox.checked = true;
                billingCheckbox.disabled = false;
            } else {
                billingCheckbox.checked = false;
                billingCheckbox.disabled = true;
            }

            // Customer email handling
            if (customerEmail) {
                customerCheckbox.checked = true;
                customerCheckbox.disabled = false;
            } else {
                customerCheckbox.checked = false;
                customerCheckbox.disabled = true;
            }

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
        form.reset();
    }

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    form.addEventListener('submit', function (e) {

        const billingChecked = billingCheckbox.checked;
        const customerChecked = customerCheckbox.checked;

        if (!billingChecked && !customerChecked) {
            e.preventDefault();
            
            notyf.error("Please select at least one email.");
            return;
        }

        const spinner = document.getElementById('sendInvoiceBtnSpinner');
        const text = document.getElementById('sendInvoiceBtnText');

        spinner.classList.remove('hidden');
        text.innerText = 'Sending...';
    });

});
</script>
<script>
document.getElementById('mark-mail-sent').addEventListener('click', function () {

    const invoiceId = this.dataset.invoiceId;

    window.showConfirm(
        'Are you sure you want to mark this email as sent?',
        'Confirm Email Status'
    ).then((result) => {

        if (result.isConfirmed) {

            const url = "{{ route('admin.crm.customers.invoice.mark-mail', ':id') }}".replace(':id', invoiceId);

            fetch(url, {
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                location.reload();
            });

        }

    });

});
</script>
@endpush
