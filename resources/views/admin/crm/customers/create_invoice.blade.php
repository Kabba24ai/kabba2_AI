@extends('admin.layouts.app')

@section('title', 'Customer Invoice')

@push('css')
@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

@php
$isEdit = isset($invoice) && $invoice->id;
$invoiceNumber = $isEdit ? $invoice->invoice_number : \App\Helpers\CustomHelper::generateInvoiceNumber();

$lockInvoiceActions = $isEdit && 
    in_array($invoice->invoice_status ?? null, ['paid', 'partial_paid']);

@endphp

{{ html()->form()->id('invoiceForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ]) ->action($isEdit
        ? route('admin.crm.customers.invoice.update', $invoice->unique_id)
        : route('admin.crm.customers.invoice.store',$customer->unique_id)
    )
    ->method('POST')->open() }}

<input type="hidden" name="invoice_data" id="invoiceDataInput" value="{{ old('invoice_data', $invoiceItems ?? '') }}">
<input type="hidden" name="subtotal" id="invoiceSubtotalInput" value="{{ old('subtotal', $invoice->subtotal ?? 0) }}">
<input type="hidden" name="tax" id="invoiceTaxInput" value="{{ old('tax', $invoice->sales_tax ?? 0) }}">
<input type="hidden" name="total" id="invoiceTotalInput" value="{{ old('total', $invoice->total ?? 0) }}">
<input type="hidden" name="customer_id" id="customer_id" value="{{ $customer->id }}">

@if($isEdit)
<input type="hidden" id="invoicePaidAmount" name="paid_amount" value="{{ $invoice->paid_amount }}">
@endif


<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
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
                <h1 class="text-2xl font-bold text-gray-900"> {{ $isEdit ? 'Edit Invoice' : 'Create Invoice' }}</h1>
                <p class="text-sm text-gray-500">Invoice <span id="invoiceNumberDisplay">#{{ $invoiceNumber }}</span> for {{ $customer->company_name ?? '' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button onclick="closeWindow()" type="button" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
            </button>

            <button type="submit" name="action" value="save_new" disabled="" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span id="invoiceActionText">{{ $isEdit ? 'Update' : 'Create' }} Invoice</span>
            </button>
        </div>
    </div>
</div>

<div class="min-h-screen bg-gray-50 mt-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <input type="hidden" value="{{ $sales_tax }}" data-sales-tax-rate>
        <!-- Left side -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-file-text w-5 h-5">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M10 9H8"></path>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                    </svg>
                    Invoice Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Invoice Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                        {!! html()->text('invoice_number')
                        ->attributes(['id' => 'invoiceNumberInput','readonly' => true])
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->value($invoiceNumber)
                        ->required() !!}
                    </div>

                    <!-- Invoice Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date</label>
                        {!! html()->text('invoice_date', old('invoice_date', \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? now())))
                        ->class('w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300')
                        ->attributes(['id' => 'invoice_date','placeholder' => 'MM-DD-YYYY','autocomplete' => 'off'])
                        ->required() !!}
                    </div>
                    <!-- Payment Terms Dropdown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date Period</label>
                        @php
                        $defaultTerm = \App\Helpers\ConfigurationHelper::getSettings(null, 'due_date_pay_upon_receipt') ?? 0;
                        @endphp
                        <select id="paymentTerms"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                            <option value="{{ $defaultTerm }}" selected>Pay Upon Receipt</option>
                            <option value="10">10 Days</option>
                            <option value="20">20 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        {!! html()->text(
                        'due_date',
                        old('due_date', isset($invoice->due_date) ? \App\Helpers\CustomHelper::formatDate($invoice->due_date) : '')
                        )
                        ->class('w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300')
                        ->attributes([
                        'id' => 'due_date',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off' ,
                        'data-parsley-afterinvoice' => '', 
                        'data-parsley-afterinvoice-message' => 'Due date must be same or after invoice date.',
                        ])
                        ->required() !!}
                    </div>


                </div>
            </div>

            <!-- Add Items -->
            <div class="bg-white rounded-lg shadow p-5 ">
                <h3 class="text-lg font-semibold mb-4 {{ $lockInvoiceActions ? 'hidden' : '' }}">Add Items to Invoice</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5 {{ $lockInvoiceActions ? 'hidden' : '' }}">
                    <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-4 h-4">
                            <path d="M5 12h14"></path>
                            <path d="M12 5v14"></path>
                        </svg>
                        Charge
                    </a>
                    <a href="javascript:void(0)" id="openDiscountModal" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-4 h-4">
                            <circle cx="12" cy="8" r="6"></circle>
                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                        </svg>
                        Discount
                    </a>
                    <a href="javascript:void(0)" id="openRefundModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        Refund
                    </a>
                    {{-- <a href="javascript:void(0)" id="openOrderModal" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-4 h-4">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                        From Order
                    </a> --}}
                    @isset($customerAccounts)

                        <a href="javascript:void(0)" id="openOrderModal" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-4 h-4">
                                <circle cx="8" cy="21" r="1"></circle>
                                <circle cx="19" cy="21" r="1"></circle>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                            </svg>
                            From Accounts
                        </a>

                    @endif
                </div>
                <!-- EMPTY STATE (when no items) -->
                <div id="emptyState" class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-12 h-12 text-gray-400 mx-auto mb-4">
                        <path d="m7.5 4.27 9 5.15"></path>
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                        <path d="m3.3 7 8.7 5 8.7-5"></path>
                        <path d="M12 22V12"></path>
                    </svg>
                    <p class="text-sm">No items added to this invoice yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Use the buttons above to add charges, discounts, refunds, or items from existing orders.</p>
                </div>
                <!-- TABLE STATE (hidden by default until items exist) -->
                <div id="itemsTable" class="overflow-x-auto hidden rounded-lg shadow border border-gray-200">
                    <table class="min-w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Description</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 whitespace-nowrap">Qty</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Unit Price</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Tax</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">Total</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItems" class="divide-y divide-gray-200">
                            <!-- rows will be added dynamically -->

                            @php
                            // Make sure $invoiceItems is always an array
                            if (is_string($invoiceItems)) {
                            $invoiceItemsArray = json_decode($invoiceItems, true) ?? [];
                            } elseif ($invoiceItems instanceof \Illuminate\Support\Collection) {
                            $invoiceItemsArray = $invoiceItems->toArray();
                            } else {
                            $invoiceItemsArray = $invoiceItems ?? [];
                            }

                            // Filter out 'order' types
                            $invoiceItems_encoded = array_filter($invoiceItemsArray, fn($item) => $item['type'] !== 'order');
                            @endphp


                            @if(!empty($invoiceItems_encoded))
                            @foreach($invoiceItems_encoded as $item)

                            @php
                            // Determine badge color based on type
                            $badgeColor=match($item['type']) { 'charge'=> 'red',
                            'order' => 'green',
                            'discount' => 'purple',
                            'refund' => 'blue',
                            default => 'red',
                            };


                            // Set custom SVG for each type
                            $badgeSvg = match($item['type']) {
                            'charge' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3">
                                <path d="M5 12h14"></path>
                                <path d="M12 5v14"></path>
                            </svg>',
                            'discount' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3">
                                <circle cx="12" cy="8" r="6"></circle>
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                            </svg>',
                            'refund' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>',
                            default => '<svg class="lucide lucide-plus w-3 h-3"></svg>',
                            };
                            @endphp


                            @if ($item['type'] !== 'order')
                            <tr data-type="{{ $item['type'] }}" data-id="{{ $item['id'] }}">

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900"> {{ $item['name'] }} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $badgeColor }}-600 text-white">
                                            {!! $badgeSvg !!}
                                            <span class="ml-1 capitalize text-xs"> {{ $item['type'] }}</span>
                                        </span>
                                    </div>
                                    @if(!empty($item['notes']))
                                    <div class="text-gray-500 text-sm">{{ $item['notes'] }}</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">{{ $item['qty'] }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap {{ !in_array($item['type'], ['order', 'charge']) ? 'text-green-600' : '' }}"> {{ !in_array($item['type'], ['order', 'charge']) ? '-' : '' }} {{ \App\Helpers\CustomHelper::formatCurrency($item['unit']) }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap {{ !in_array($item['type'], ['order', 'charge']) ? 'text-green-600' : '' }}">  {{ !in_array($item['type'], ['order', 'charge']) ? '-' : '' }} {{ \App\Helpers\CustomHelper::formatCurrency($item['tax']) }} </td>
                                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap {{ !in_array($item['type'], ['order', 'charge']) ? 'text-green-600' : '' }}">  {{ !in_array($item['type'], ['order', 'charge']) ? '-' : '' }} {{ \App\Helpers\CustomHelper::formatCurrency($item['total']) }}</td>
                                <td class="px-4 py-3 text-center h-full items-center justify-center gap-3 whitespace-nowrap">

                                 @if(!in_array($invoice->invoice_status, ['paid', 'partial_paid']))
                                        <button type="button" class="text-blue-600 edit-btn mr-2"><x-heroicon-o-pencil-square class="w-4 h-4" /></button>
                                        <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                    @endif
                                </td>


                            </tr>
                            @endif
                            @endforeach
                            @endif

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Notes -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Invoice Notes</h3>
                <textarea rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" name="invoice_notes" placeholder="Enter any additional notes for this invoice...">{{ old('invoice_notes', isset($invoice) ? $invoice->invoice_notes : '') }}</textarea>
            </div>
        </div>

        <!-- Right side -->
        <div class="space-y-6">
            <!-- Bill To -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-5 h-5 mr-2">
                        <rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect>
                        <path d="M9 22v-4h6v4"></path>
                        <path d="M8 6h.01"></path>
                        <path d="M16 6h.01"></path>
                        <path d="M12 6h.01"></path>
                        <path d="M12 10h.01"></path>
                        <path d="M12 14h.01"></path>
                        <path d="M16 10h.01"></path>
                        <path d="M16 14h.01"></path>
                        <path d="M8 10h.01"></path>
                        <path d="M8 14h.01"></path>
                    </svg>
                    Bill To
                </h3>
                <p class="font-semibold">{{ $customer->company_name ?? 'N/A' }}</p>
                <p class="text-gray-600">{{ $customer->full_name ?? 'N/A' }}</p>

                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                    {{ $customer->email ?? 'N/A' }}
                </div>
                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                    {{ App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                </div>

                @php
                $billingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Billing' );
                $shippingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Shipping');

                $defaultAddresses = [
                ['label' => 'Billing', 'data' => $billingAddress],
                ['label' => 'Delivery', 'data' => $shippingAddress],
                ];
                @endphp

                @foreach ($defaultAddresses as $index => $addressItem)
                @php $addresse = $addressItem['data']; @endphp

                 {{-- Heading --}}
                <div class="text-sm font-semibold text-gray-900 mt-3">
                    {{ $addressItem['label'] }} Address
                </div>


                <div class="flex items-start text-sm text-gray-600 gap-2 mt-2">
    
                <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900 flex-shrink-0 mt-1" />

                <div class="flex-1">

                    @if ($addressItem['label'] === 'Shipping' && !empty($customer->same_as_billing))
                        
                        <div class="text-gray-500 italic">
                            Same as billing address
                        </div>

                    @elseif($addresse)

                        <div class="grid gap-x-8 gap-y-1">

                            <!-- Row 1 -->
                            <div>
                                {{ $addresse->address ?? '' }}
                               
                            </div>

                         
                            <div>
                                 @if(!empty($addresse->city))
                                     {{ $addresse->city }} ,
                                @endif 
                                @if(!empty($addresse->state?->name))
                                    {{ $addresse->state->name }},
                                @endif

                                {{ $addresse->zip_code ?? '' }}

                                @if(!empty($addresse->country))
                                    , {{ $addresse->country }}
                                @endif
                            </div>

                        </div>

                    @else
                        <div class="text-gray-400">
                            No address provided.
                        </div>
                    @endif

                </div>

            </div>
                @endforeach


                <div class="flex items-center mt-3 gap-1">

                    @if ($customer->tax_status === 'Exempt')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check w-4 h-4 mr-2 text-green-600">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-600">{{ $customer->tax_status ?? 'N/A' }}</span>
                    @else
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-600">{{ $customer->tax_status ?? 'N/A' }}</span>
                    @endif

                </div>

                 @if ($isEdit)


                    @if(isset($invoice) && $invoice->id)

                        @php
                            $statusColors = [
                                'paid' => 'green',
                                'partial_paid' => 'blue',
                                'overdue' => 'red',
                                'pending' => 'yellow',
                            ];

                            $color = $statusColors[$invoice->invoice_status] ?? 'gray';
                        @endphp

                        <div class="mt-3">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ ucfirst(str_replace('_', ' ', $invoice->invoice_status)) }}
                            </span>
                        </div>

                    @endif

                        {{--  Only show Payment button if NOT fully paid --}}
                    @if(isset($invoice) && $invoice->invoice_status !== 'paid')

                        <div class="flex flex-wrap justify-center gap-2 pt-5">
                            
                            <a href="javascript:void(0)" id="openTemplatesModal" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-md flex items-center">
                                
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2">
                                        <line x1="12" x2="12" y1="2" y2="22"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>

                                Payment
                            </a>

                        </div>
                    @endif

                 @endif

            </div>

            <div class="bg-white rounded-lg shadow p-6">

                <h3 class="text-lg font-semibold mb-4">Invoice Summary</h3>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Subtotal:</span>
                    <span class="font-medium text-gray-900" id="invoice-subtotal">$0.00</span>
                </div>

                <!-- Sales Tax -->
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Sales Tax:</span>
                    <span class="font-medium text-gray-900" id="invoice-tax">$0.00</span>
                </div>

                 <!-- Discount -->
                <div class="flex justify-between py-1 text-sm hidden" id="discount-row">
                    <span class="text-gray-700">Discount:</span>
                    <span class="text-green-600 font-medium" id="invoice-discount">-$0.00</span>
                </div>

                <!-- Refund -->
                <div class="flex justify-between py-1 text-sm hidden" id="refund-row">
                    <span class="text-gray-700">Refund:</span>
                    <span class="text-green-600 font-medium" id="invoice-refund">-$0.00</span>
                </div>

                <!-- Divider -->
                <hr class="my-2 border-gray-300">

                <!-- Total -->
                <div class="flex justify-between pt-2 font-bold text-lg">
                    <span>Total:</span>
                    <span class="text-gray-900" id="invoice-total">$0.00</span>
                </div>


                <!-- Paid & Open Amount (Only Edit Mode) -->
                @if($isEdit)
                <hr class="my-2 border-gray-200">

                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Paid Amount:</span>
                    <span class="text-green-600 font-medium" id="invoice-paid">
                        ${{ number_format($invoice->paid_amount ?? 0, 2) }}
                    </span>
                </div>

                <div class="flex justify-between py-1 text-sm">
                    <span class="text-gray-700">Open Amount:</span>
                    <span class="text-red-600 font-medium" id="invoice-open">
                        ${{ number_format($invoice->open_amount ?? 0, 2) }}
                    </span>
                </div>
                @endif

            </div>

            <div class="bg-gray-50 px-4 py-4  border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button     onclick="window.location.href='{{ route('admin.crm.customers.invoice.index', $customer->unique_id) }}'"  
 type="button" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
                        </button>

                        <button type="submit" name="action" value="save_new" disabled="" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium shadow transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span id="invoiceActionText2">{{ $isEdit ? 'Update' : 'Create' }} Invoice</span>
                        </button>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>





{{ html()->form()->close() }}
<!-- New Charge Wrapper -->
@include('admin.crm.customers.partials._invoice_charge')

<!-- New Discount Wrapper -->
@include('admin.crm.customers.partials._invoice_discount')

<!-- New Refund Wrapper -->
@include('admin.crm.customers.partials._invoice_refund')

<!-- New Refund Wrapper -->
@include('admin.crm.customers.partials._invoice_payment')




<!-- From Order Wrapper -->
<div id="orderModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-5 h-5 mr-2 text-blue-600">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Select Account Entries to Add to Invoice </h2>
                </div>
                <button id="closeOrderModalBtn" type="button" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 pb-6 overflow-y-auto">
                <div class="overflow-x-auto rounded-lg shadow border border-gray-200 ">
                    @isset($customerAccounts)
                        @if($customerAccounts->count())
                            <table class="w-full text-left" id="orders-table">
                                <thead class="bg-gray-100 text-sm font-semibold text-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 whitespace-nowrap">Type</th>
                                        <th class="px-4 py-3 whitespace-nowrap">Date</th>
                                        <th class="px-4 py-3 whitespace-nowrap">Description </th>
                                        <th class="px-4 py-3 whitespace-nowrap">Total</th>
                                        <th class="px-4 py-3 whitespace-nowrap">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-sm ">

                                     @php
                                        $typeStyles = [
                                        'charge' => [
                                        'bg' => 'bg-red-100',
                                        'text' => 'text-red-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'plus',
                                        ],
                                        'payment' => [
                                        'bg' => 'bg-green-100',
                                        'text' => 'text-green-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'credit-card',
                                        ],
                                        'order' => [
                                        'bg' => 'bg-red-100',
                                        'text' => 'text-red-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'cart',
                                        ],
                                        'discount' => [
                                        'bg' => 'bg-purple-100',
                                        'text' => 'text-purple-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'award',
                                        ],
                                        'credit' => [
                                        'bg' => 'bg-yellow-100',
                                        'text' => 'text-yellow-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'arrow-down-left',
                                        ],
                                        'debit' => [
                                        'bg' => 'bg-orange-100',
                                        'text' => 'text-orange-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'arrow-up-right',
                                        ],
                                        'refund' => [
                                        'bg' => 'bg-blue-100',
                                        'text' => 'text-blue-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'trending-up',
                                        ],
                                        ];
                                    @endphp

                                    @foreach ($customerAccounts as $account)
                                     @php
                                    $style = $typeStyles[$account->type] ?? $typeStyles['charge'];
                                    @endphp

                                    <tr>
                                        <td class="px-4 py-3 font-medium whitespace-nowrap flex gap-2 items-center">
                                            
                                            {{-- {!! $order->view_link !!}  --}}

                                             <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $style['bg'] }} {{ $style['text'] }}">
                                                @if ($style['icon'] === 'plus')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-plus w-4 h-4">
                                                    <path d="M5 12h14" />
                                                    <path d="M12 5v14" />
                                                </svg>
                                                @elseif ($style['icon'] === 'credit-card')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-credit-card w-4 h-4">
                                                    <rect width="20" height="14" x="2" y="5" rx="2" />
                                                    <line x1="2" x2="22" y1="10" y2="10" />
                                                </svg>
                                                @elseif ($style['icon'] === 'award')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-award w-4 h-4">
                                                    <circle cx="12" cy="8" r="6" />
                                                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                                </svg>
                                                @elseif ($style['icon'] === 'arrow-down-left')
                                                <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 7L7 17" />
                                                    <path d="M17 17H7V7" />
                                                </svg>
                                                @elseif ($style['icon'] === 'arrow-up-right')
                                                <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                                    <polyline points="16 7 22 7 22 13" />
                                                </svg>
                                                @elseif ($style['icon'] === 'cart')
                                                <x-heroicon-o-shopping-cart class="h-4 w-4" />
                                                @elseif ($style['icon'] === 'trending-up')

                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-trending-up w-4 h-4">
                                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                                    <polyline points="16 7 22 7 22 13" />
                                                </svg>



                                                @endif
                                                <span class="ml-1 capitalize">{{ $account->type }}</span>
                                            </span>
                                            @if($account->type === 'order' && $account->order)
                                                {!! $account->order->view_link !!}
                                            @endif
                                        
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">

                                              
                                              {{ App\Helpers\CustomHelper::formatDate($account->date) ?? 'N/A' }}
                                                

                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">

                                            @if($account->type === 'order' && $account->order)
                                                {!! $account->order->products->pluck('product_name')->join(', <br> ') !!}
                                            @else
                                                {{ $account->reason ?? '-' }}
                                            @endif
                                            
                                        </td>

                                        <td class="px-4 py-3 font-semibold whitespace-nowrap {{ $style['amount'] }}">
    
                                            @if($account->type === 'order' && $account->order)

                                                {{ $style['sign'] }}
                                                {{ \App\Helpers\CustomHelper::formatCurrency($account->order->grand_total) }}

                                            @else

                                                @php
                                                    $amount = (float) ($account->amount ?? 0);
                                                    $taxRate = (float) ($account->sales_tax ?? 0);
                                                    $taxType = $account->sales_tax_type ?? null;
                                                    $type = $account->type;

                                                    $total = $amount;

                                                    if ($taxRate > 0) {
                                                        $isReverse = ($type === 'payment') || 
                                                                    ($type === 'charge' && $taxType === 'reverse');

                                                        if (!$isReverse) {
                                                            $total += ($amount * $taxRate);
                                                        }
                                                    }

                                                    $total = round($total, 2);
                                                @endphp

                                                {{ $style['sign'] }}
                                                {{ \App\Helpers\CustomHelper::formatCurrency($total) }}

                                            @endif

                                        </td>


                                        <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                            
                                            <button
                                            @if($account->type === 'order' && $account->order)
                                                @if ($account->order->last_payment_status == 'Paid at Front Desk' || $account->last_payment_status == 'Paid by CC on File' || $account->last_payment_status == 'Paid by Direct Bank' || $account->last_payment_status == 'Paid')
                                                disabled
                                                @endif

                                                data-order-id="{{ $account->order->unique_id }}"

                                            @endif
                                                type="button" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 add-to-invoice-btn"  data-account-id="{{ $account->unique_id }}"
                                                data-type="{{ $account->type }}"

                                                data-reason="{{ $account->reason }}"

                                                data-unit="{{ $account->amount }}"
                                                data-sales_tax="{{ $account->sales_tax }}"

                                                 data-responsible_person_id="{{ $account->responsible_person_id }}"
                                                  data-sales_tax="{{ $account->sales_tax }}"
                                                   data-notes="{{ $account->notes }}"
                                                >
                                                + Add to Invoice
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 p-5 text-center">No account entries available for invoicing.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details Wrapper -->
<div id="viewdetailsModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Order Details - ORD-2025-001</h2>
                </div>
                <button type="button" id="closeViewdetailsModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="p-6 space-y-6 overflow-y-auto">

                <!-- Order Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Order Date</p>
                        <p class="font-medium">Jan 15, 2025</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Order Total</p>
                        <p class="font-semibold text-lg">$1,249.95</p>
                    </div>
                </div>

                <!-- Add all products -->
                <div class="flex flex-col sm:flex-row sm:justify-between items-center p-4 border rounded-lg bg-blue-50">
                    <div>
                        <p class="text-sm font-medium text-blue-900">Add All Products from Order</p>
                        <p class="text-xs text-blue-700 mt-1 mb-2">This will add each product as individual line items. Total: $1,249.95</p>
                    </div>
                    <!-- <button type="button" class="bg-green-600 text-white px-6 py-2 rounded-md add-to-invoice-btn text-sm">
                        + Add to Invoice
                    </button> -->
                </div>

                <!-- Product Items -->
                <div class="space-y-4">
                    <!-- Product 1 -->
                    <div class="flex justify-between items-start p-4 border rounded-lg whitespace-nowrap">
                        <div>
                            <p class="font-medium">Premium Widget Set</p>
                            <p class="text-sm text-gray-600">SKU: PWS-001</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Color: Blue</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Size: Large</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Material: Aluminum</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">$999.95</p>
                            <p class="text-sm text-gray-500">5 × $199.99</p>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="flex justify-between items-start p-4 border rounded-lg whitespace-nowrap">
                        <div>
                            <p class="font-medium">Installation Service</p>
                            <p class="text-sm text-gray-600">SKU: INST-001</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Service Type: On-site Installation</span>
                                <span class="inline-block bg-gray-100 text-xs text-gray-600 px-2 py-1 rounded">Technician Level: Senior</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">$250.00</p>
                            <p class="text-sm text-gray-500">1 × $250.00</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('js')

<script>
    const invoiceInput = document.getElementById("invoiceNumberInput");
    const invoiceDisplay = document.getElementById("invoiceNumberDisplay");

    invoiceInput.addEventListener("input", function() {
        const value = this.value.trim();
        invoiceDisplay.textContent = value ? `#${value}` : "#INV-2025-920";
    });


    const TYPE_CONFIG = {
        charge: {
            sign: '+',
            badgeClass: 'bg-red-600',
            textClass: 'text-gray-900',
            negative: false
        },
        discount: {
            sign: '-',
            badgeClass: 'bg-purple-600',
            textClass: 'text-green-600',
            negative: true
        },
        refund: {
            sign: '-',
            badgeClass: 'bg-blue-600',
            textClass: 'text-green-600',
            negative: true
        },
        payment: {
            sign: '-',
            badgeClass: 'bg-green-600',
            textClass: 'text-green-600',
            negative: true
        }
    };


    function renderInvoiceRow(item, type,accountId) {

        const config = TYPE_CONFIG[type] || TYPE_CONFIG.charge;

        const sign = config.sign;
        const textColor = config.textClass;
        const badgeClass = config.badgeClass;

        const amountDisplay = config.negative
            ? `- $${item.unit.toFixed(2)}`
            : `$${item.unit.toFixed(2)}`;

        const taxDisplay = config.negative
            ? `- $${item.tax.toFixed(2)}`
            : `$${item.tax.toFixed(2)}`;

        const totalDisplay = config.negative
            ? `- $${item.total.toFixed(2)}`
            : `$${item.total.toFixed(2)}`;

        const row = document.createElement("tr");
        row.dataset.type = type;
        row.dataset.id = item.id;
        row.dataset.accountId = accountId;

        

        row.innerHTML = `
            <td class="px-4 py-3 whitespace-nowrap">
                <div class="font-medium text-gray-900">
                    ${item.name}
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${badgeClass} text-white">
                        <span class="ml-1 capitalize text-xs">${type}</span>
                    </span>
                </div>
                ${item.notes ? `<div class="text-gray-500 text-sm">${item.notes}</div>` : ""}
            </td>
            <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${item.qty}</td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap ${textColor}">
                ${amountDisplay}
            </td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap ${textColor}">
                ${taxDisplay}
            </td>
            <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap ${textColor}">
                ${totalDisplay}
            </td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
                
                <button type="button" class="text-red-600 delete-btn">
                    <x-heroicon-o-trash class="w-4 h-4" />
                </button>
            </td>
        `;

        document.getElementById("invoiceItems").appendChild(row);
        document.getElementById("emptyState").classList.add("hidden");
        document.getElementById("itemsTable").classList.remove("hidden");
        
        document.getElementById('orderModalWrapper').style.display = 'none' ;

    }
   
</script>
 

<script>
    function updateInvoiceButton() {
        const invoiceItems = document.querySelector('#invoiceItems');
        const btn1 = document.querySelector('#invoiceActionText');
        const btn2 = document.querySelector('#invoiceActionText2');
        const saveButtons = document.querySelectorAll('button[name="action"][value="save_new"]');

        const hasRows = invoiceItems && invoiceItems.querySelectorAll('tr').length > 0;

        // Update both spans
        if (btn1) btn1.textContent = hasRows ? 'Save Invoice' : 'Create Invoice';
        if (btn2) btn2.textContent = hasRows ? 'Save Invoice' : 'Create Invoice';

        // Enable/disable both buttons
        saveButtons.forEach(btn => btn.disabled = !hasRows);
    }

    // Run on page load
    updateInvoiceButton();
</script>


<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));

    // Select the hidden input by its data attribute
    const salesTaxInput = document.querySelector('[data-sales-tax-rate]');

    // Assign its value (converted to a number) to the global variable
    window.SALES_TAX_RATE = parseFloat(salesTaxInput.value) || 0;

    // Global array to store all product data
    // --- PRELOAD from PHP ---
    let invoice_data = []; // pre-filled if edit page

    // Global function to add products to the invoice_data array
    function addInvoiceProduct(products, type) {

        // console.table(products);

        products.forEach(p => {
            const product = {
                ...p, // Spread operator to copy all properties from the original product object
                type: type, // Add the 'type' property
            };
            invoice_data.push(product); // Add the new product object to the array
        });
        // console.table(invoice_data);
    }

    // generate a unique ID for every arry item

    function generateUniqueId(prefix = "item") {
        return prefix + "_" + Date.now() + "_" + Math.floor(Math.random() * 100000);
    }


    function parseCurrency(value) {
        // Remove everything except digits, minus sign, and dot
        return Number(value.replace(/[^0-9.-]+/g, "")) || 0;
    }


    const invoiceItemsTBody = document.getElementById("invoiceItems"); // Make sure to get this element


    (function () {

        function updateInvoiceSummary() {

            const subtotalElem = document.querySelector('#invoice-subtotal');
            const totalElem = document.querySelector('#invoice-total');
            const taxElem = document.querySelector('#invoice-tax');

            const discountElem = document.querySelector('#invoice-discount');
            const refundElem = document.querySelector('#invoice-refund');
            const discountRow = document.querySelector('#discount-row');
            const refundRow = document.querySelector('#refund-row');

            const createBtn = document.querySelector('button[name="action"][value="save_new"]');

            const subtotalInput = document.getElementById("invoiceSubtotalInput");
            const taxInput = document.getElementById("invoiceTaxInput");
            const totalInput = document.getElementById("invoiceTotalInput");
            const invoiceDataInput = document.getElementById("invoiceDataInput");

            const paidElem = document.querySelector('#invoice-paid');
            const openElem = document.querySelector('#invoice-open');


            const paidInput = document.getElementById('invoicePaidAmount');


            let subtotal = 0;
            let totalTax = 0;
            let totalDiscount = 0;
            let totalRefund = 0;

            const rows = invoiceItemsTBody.querySelectorAll("tr");

            rows.forEach(row => {

                const type = row.dataset.type;

                const priceCell = row.querySelector("td:nth-child(3)");
                const taxCell = row.querySelector("td:nth-child(4)");

                let price = parseCurrency(priceCell?.textContent || '0');
                let tax = parseCurrency(taxCell?.textContent || '0');

                if (type === "charge" || type === "order") {
                    subtotal += price;
                    totalTax += tax;
                }

                if (type === "discount") {
                    totalDiscount += Math.abs(price);
                    totalTax -= Math.abs(tax);
                }

                if (type === "refund") {
                    totalRefund += Math.abs(price);
                    totalTax -= Math.abs(tax);
                }
            });

            subtotal = Math.max(0, subtotal);
            totalTax = Math.max(0, totalTax);

            let finalTotal = subtotal + totalTax - totalDiscount - totalRefund;
            finalTotal = Math.max(0, finalTotal);

            // --------------------
            // Update UI
            // --------------------

            if (subtotalElem) subtotalElem.textContent = `$${subtotal.toFixed(2)}`;
            if (taxElem) taxElem.textContent =
                totalTax > 0 ? `$${totalTax.toFixed(2)}` : 'Tax Exempt';

            if (discountRow && discountElem) {
                if (totalDiscount > 0) {
                    discountRow.classList.remove('hidden');
                    discountElem.textContent = `-$${totalDiscount.toFixed(2)}`;
                } else {
                    discountRow.classList.add('hidden');
                }
            }

            if (refundRow && refundElem) {
                if (totalRefund > 0) {
                    refundRow.classList.remove('hidden');
                    refundElem.textContent = `-$${totalRefund.toFixed(2)}`;
                } else {
                    refundRow.classList.add('hidden');
                }
            }

            if (totalElem) totalElem.textContent = `$${finalTotal.toFixed(2)}`;

            // --------------------
            // Handle Paid/Open (Edit Mode Only)
            // --------------------
            if (paidElem && openElem) {

                let paidAmount = parseFloat(paidElem.dataset.paid || paidElem.textContent.replace(/[^0-9.-]+/g,"")) || 0;

                let openAmount = finalTotal - paidAmount;
                openAmount = Math.max(0, openAmount);

                paidElem.textContent = `$${paidAmount.toFixed(2)}`;
                openElem.textContent = `$${openAmount.toFixed(2)}`;
            }


            
            if (paidInput && paidElem && openElem) {

                let paidAmount = parseFloat(paidInput.value) || 0;
                let openAmount = finalTotal - paidAmount;

                openAmount = Math.max(0, openAmount);

                paidElem.textContent = `$${paidAmount.toFixed(2)}`;
                openElem.textContent = `$${openAmount.toFixed(2)}`;
            }

            if (createBtn) {
                createBtn.disabled = rows.length === 0;
            }

            // --------------------
            // Hidden Inputs
            // --------------------

            if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
            if (taxInput) taxInput.value = totalTax.toFixed(2);
            if (totalInput) totalInput.value = finalTotal.toFixed(2);
            if (invoiceDataInput) invoiceDataInput.value = JSON.stringify(invoice_data);

            updateInvoiceButton();
        }

        window.updateInvoiceSummary = updateInvoiceSummary;

    })();



    /**
     * Disable or enable the "+ Add to Invoice" button for a specific accountId
     * @param {string|number} account - The account unique ID
     * @param {boolean} disable - true to disable, false to enable
     */
    function toggleAddToInvoiceButton(account, disable = true) {
        const btn = document.querySelector(`.add-to-invoice-btn[data-account-id="${account}"]`);
        
        if (!btn) return;

        if (disable) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
</script>
<script>
    // Pass directly from Blade
    const existingOrderItems = @json($orderItems ?? []);
    const existingOtherItems = @json($otherItems ?? []);

    // console.table(existingOtherItems);

    // Add existingOtherItems into the global invoice_data array
    if (Array.isArray(existingOtherItems) && existingOtherItems.length > 0) {
        existingOtherItems.forEach(item => {
            invoice_data.push(item); // Just add to the array, no table rendering
        });
        console.log("Added existingOtherItems to invoice_data:");
        // console.table(invoice_data);

        document.getElementById("emptyState").classList.add("hidden");
        document.getElementById("itemsTable").classList.remove("hidden");

        window.updateInvoiceSummary();
    }
</script>



<!-- orderModalWrapper -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const orderModal = document.getElementById('orderModalWrapper');
        const detailsModal = document.getElementById('viewdetailsModalWrapper');

        const openOrderBtn = document.getElementById('openOrderModal');
        const closeOrderBtn = document.getElementById('closeOrderModalBtn');

        const openDetailsBtn = document.getElementById('openviewdetailsModal');
        const closeDetailsBtn = document.getElementById('closeViewdetailsModalBtn');

        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState");


        // Render existing order items if in edit mode
        if (existingOrderItems.length > 0) {
            addProductsToTable(existingOrderItems);
        }

        // --- Order Modal ---
        if (openOrderBtn) openOrderBtn.addEventListener('click', () => orderModal.style.display = 'flex');
        if (closeOrderBtn) closeOrderBtn.addEventListener('click', () => orderModal.style.display = 'none');
        orderModal.addEventListener('click', e => {
            if (e.target === orderModal) orderModal.style.display = 'none';
        });

        // --- View Details Modal ---
        if (openDetailsBtn) openDetailsBtn.addEventListener('click', () => detailsModal.style.display = 'flex');
        if (closeDetailsBtn) closeDetailsBtn.addEventListener('click', () => detailsModal.style.display = 'none');
        detailsModal.addEventListener('click', e => {
            if (e.target === detailsModal) detailsModal.style.display = 'none';
        });

        // Add products into invoice table
        function addProductsToTable(products) {

            // Use the global function to add products to the data array
            addInvoiceProduct(products, 'order');

            products.forEach(p => {
                const tr = document.createElement("tr");

                tr.dataset.type = "order";
                tr.dataset.id = p.id;
                tr.dataset.orderId = p.orderId;

                tr.dataset.accountId = p.accountId;


                // toggleAddToInvoiceButton(p.orderId, true); // disable

                tr.innerHTML = `
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${p.name} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-600 text-white">
                                                                                                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-3 h-3">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg> <span class="ml-1 capitalize text-xs">Order</span>
                                      </span></div>
                    <div class="text-gray-500 text-xs"> SKU: ${p.sku} • Order: ${p.orderId}</div>
                    <div class="flex flex-wrap gap-2 mt-1">
                    ${p.extras.map(tag => `<span class="px-2 py-1 text-xs bg-gray-100 rounded">${tag}</span>`).join("")}
                    </div>
                    <div class="text-gray-400 text-xs mt-1">From Order ${p.orderId}</div>
                </td>
                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${p.qty}</td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${Number(p.unit).toFixed(2)}</td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${Number(p.tax).toFixed(2)}</td>
                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${Number(p.total).toFixed(2)}</td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-3">
                    @if(!isset($invoice) || !in_array($invoice->invoice_status, ['paid', 'partial_paid']))
                        <button type="button" class="text-red-600 delete-btn">
                            <x-heroicon-o-trash class="w-4 h-4" />
                        </button>
                    @endif
                    </div>
                </td>
            `;
                invoiceItemsTBody.appendChild(tr);
            });

            if (emptyState) emptyState.classList.add("hidden");
            itemsTableWrap.classList.remove("hidden");

            window.updateInvoiceSummary();


        }

        function calculateTransactionTotals(amount, taxRate, type, taxType) {

            amount = parseFloat(amount) || 0;
            taxRate = parseFloat(taxRate) || 0;

            let base = amount;
            let tax = 0;
            let total = amount;

            if (taxRate > 0) {

                const isReverse = (type === 'payment') || 
                                (type === 'charge' && taxType === 'reverse');

                if (isReverse) {
                    // Tax INCLUDED in amount
                    base = amount / (1 + taxRate);
                    tax = amount - base;
                    total = amount;
                } else {
                    // Tax ADDED on top
                    tax = amount * taxRate;
                    total = amount + tax;
                }
            }

            // Round properly
            base = Math.round(base * 100) / 100;
            tax = Math.round(tax * 100) / 100;
            total = Math.round(total * 100) / 100;

            return { base, tax, total };
        }

        // Handle Add to Invoice buttons
        document.body.addEventListener("click", async (e) => { 
            const btn = e.target.closest(".add-to-invoice-btn");
            if (!btn) return;

            const accountId = btn.dataset.accountId;
            const type = btn.dataset.type;

            if (type === "order") {
                

              const orderId = btn.dataset.orderId;

            //   console.log('orderId:-',orderId);

                const orderDetailsBaseUrl = "{{ route('admin.crm.customers.invoice.orders.details', ['unique_id' => ':id']) }}";

                const wrapper = document.getElementById('orders-table'); // loader wrapper
                if (wrapper) {
                    wrapper.classList.add('opacity-50', 'pointer-events-none'); // show loader
                }

                try {
                    const url = orderDetailsBaseUrl.replace(':id', orderId);
                    const res = await fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json"
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`HTTP error ${res.status}`);
                    }

                    let data;
                    try {
                        data = await res.json();
                    } catch (jsonErr) {
                        throw new Error("Response was not valid JSON");
                    }

                    if (data.success === false) {
                        throw new Error(data.message || "Server returned an error");
                    }

                    const products = (data.products || []).map(p => ({
                        id: p.order_products_unique_id,
                        name: p.name,
                        sku: p.sku ?? "-",
                        qty: p.qty,
                        unit: parseFloat(p.unit_price),
                        tax: parseFloat(p.tax) || 0,
                        total: parseFloat(p.total),
                        extras: (p.extras || []).filter(x => x),
                        orderId: data.unique_id,
                        account_id:data.accountid,
                    })); 

                    // console.log(products);

                    addProductsToTable(products);

                    // Close modals: if from 2nd modal → close both
                    if (btn.closest("#viewdetailsModalWrapper")) {
                        detailsModal.style.display = "none";
                        orderModal.style.display = "none";
                    } else if (btn.closest("#orderModalWrapper")) {
                        orderModal.style.display = "none";
                    }

                } catch (error) {
                    console.error(" Failed to fetch order products:", error);
                    notyf.error(error.message || "Unable to load order products.");
                } finally {
                    // Always remove loader
                    if (wrapper) {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    }
                }

            }else{

                const desc = btn.dataset.reason || btn.dataset.type || 'Item';
                const qty = 1;
                const price = parseFloat(btn.dataset.unit) || 0;
                const taxRate = parseFloat(btn.dataset.sales_tax) || 0;
                const taxType = btn.dataset.sales_tax_type || null;
                const responsibleId = btn.dataset.responsible_person_id || null;
                const notes = btn.dataset.notes || '';

                if (price <= 0) {
                    notyf.error("Invalid amount");
                    return;
                }

                //  Single source of truth
                const { base, tax, total } = calculateTransactionTotals(
                    price,
                    taxRate,
                    type,
                    taxType
                );

                const uniqueId = generateUniqueId(type);

                const item = {
                    id: uniqueId,
                    name: desc,
                    qty: 1,
                    unit: base,
                    tax: tax,
                    total: total,
                    responsible_id: responsibleId,
                    reference: null,
                    notes: notes,
                    account_id: accountId,
                };

                addInvoiceProduct([item], type);

                renderInvoiceRow(item, type ,accountId);

                notyf.success(`${type} added`);
                window.updateInvoiceSummary();


            }  

              toggleAddToInvoiceButton(accountId, true); // disable
            

        });

        // Delete row
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;

            const row = btn.closest("tr");
            const itemId = row.dataset.id;
            const itemType = row.dataset.type;
            const orderId = row.dataset.orderId || null;
            const invoicePaidInput = document.getElementById("invoicePaidAmount");

             const accountId = row.dataset.accountId || null;
            console.log("Delete clicked:", {
                itemId,
                itemType,
                orderId
            });

            if (itemType === "order" && orderId && invoicePaidInput) {
                   notyf.error("This order cannot be removed because the invoice has already been created.");
                    return;
            }

            if (itemType === "order" && orderId) {

    
                // Remove all rows with same order id
                const rowsToDelete = invoiceItemsTBody.querySelectorAll(`tr[data-order-id="${orderId}"]`);

                const obtn = document.querySelector(`.add-to-invoice-btn[data-order-id="${orderId}"]`)

                const accountId = obtn.dataset.accountId;

                 toggleAddToInvoiceButton(accountId, false); // enable

                console.log(`Deleting ${rowsToDelete.length} rows for orderId:`, orderId);

                rowsToDelete.forEach(r => {
                    console.log("Removing row:", r.dataset.id);
                    r.remove();
                });

                // Remove from global array
                const beforeLength = invoice_data.length;
                invoice_data = invoice_data.filter(p => p.orderId !== orderId);
                console.log("invoice_data reduced:", beforeLength, "→", invoice_data.length);

                // Notify
                notyf.success("All items from the order have been deleted.");
            } else {
                // Remove only this row
                console.log("Removing single row:", itemId);
                row.remove();

                // Remove from global array
                const index = invoice_data.findIndex(p => p.id === itemId);
                if (index !== -1) {
                    invoice_data.splice(index, 1);
                    console.log("Removed from invoice_data:", itemId);
                }

                // Notify
                notyf.success("Invoice item deleted successfully.");
            }


            if (!invoiceItemsTBody.querySelector("tr")) {
                itemsTableWrap.classList.add("hidden");
                if (emptyState) emptyState.classList.remove("hidden");
            }

            window.updateInvoiceSummary();

            toggleAddToInvoiceButton(accountId, false); // enable

        });
    });
</script>
<!-- orderModalWrapper -->

<script>
    const amountInput = document.getElementById("amount");
    const clueBox = document.getElementById("clueBox");
    const amountPreview = document.getElementById("amountPreview");
    const taxPreview = document.getElementById("taxPreview");
    const totalPreview = document.getElementById("totalPreview");

    amountInput.addEventListener("input", function() {
        this.value = this.value.replace(/[^0-9.]/g, ""); // only digits & dot

        const amount = parseFloat(this.value) || 0;
        const taxRate = window.SALES_TAX_RATE;
        const tax = amount * taxRate;
        const total = amount + tax;

        amountPreview.textContent = `$${amount.toFixed(2)}`;
        taxPreview.textContent = `$${tax.toFixed(2)}`;
        totalPreview.textContent = `$${total.toFixed(2)}`;

        // Show or hide the clue box
        if (this.value.trim() !== "") {
            clueBox.classList.remove("hidden");
        } else {
            clueBox.classList.add("hidden");
        }
    });
</script>

<script>
    function addItemToInvoice() {
        document.getElementById("emptyState").classList.add("hidden");
        document.getElementById("itemsTable").classList.remove("hidden");
    }
</script>

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
@if ($isEdit)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const statusSelect = document.getElementById('invoice_status');
        const paymentWrapper = document.getElementById('payment-method-wrapper');
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');

        //  Show/hide payment methods based on invoice status
        function togglePaymentMethods() {
            if (statusSelect.value === 'paid') {
                paymentWrapper.classList.remove('hidden');
            } else {
                paymentWrapper.classList.add('hidden');
            }
        }

        // Generic confirmation dialog
        function showChangeConfirm(newValue, callback) {
            //  Format the displayed label
            let displayValue = newValue;
            if (newValue.toLowerCase() === 'cheque') {
                displayValue = 'Check';
            }

            window.showConfirm(
                `You are about to change the status to "${displayValue}". Do you want to continue?`,
                'Confirm Change'
            ).then(result => callback(result.isConfirmed));
        }

        // Store initial value before change
        statusSelect.addEventListener('mousedown', () => {
            statusSelect.dataset.initial = statusSelect.value;
        });

        //  Handle invoice status change with confirm
        statusSelect.addEventListener('change', e => {
            const newValue = e.target.value;
            e.preventDefault();
            statusSelect.value = statusSelect.dataset.initial;

            showChangeConfirm(newValue, confirmed => {
                if (confirmed) {
                    statusSelect.value = newValue;
                    statusSelect.dataset.initial = newValue;
                    togglePaymentMethods(); // Only show payment section after confirm
                } else {
                    togglePaymentMethods(); // Keep correct visibility on cancel
                }
            });
        });

        //  Initial check when page loads
        togglePaymentMethods();

        //  Handle payment method radio change with confirm
        paymentRadios.forEach(radio => {
            if (radio.checked) radio.dataset.initial = 'checked';

            radio.addEventListener('click', e => {
                const clickedValue = radio.value;
                const prevChecked = document.querySelector('input[name="payment_method"][data-initial="checked"]');

                showChangeConfirm(clickedValue, confirmed => {
                    if (confirmed) {
                        paymentRadios.forEach(r => r.dataset.initial = '');
                        radio.dataset.initial = 'checked';
                        radio.checked = true;
                    } else {
                        if (prevChecked) prevChecked.checked = true;
                    }
                });

                e.preventDefault(); // Prevent instant toggle before confirm
            });
        });
    });
</script>
@endif

<!-- JS Section -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const invoiceDateInput = document.getElementById('invoice_date');
        const dueDateInput = document.getElementById('due_date');
        const paymentTermsSelect = document.getElementById('paymentTerms');

        function calculateDueDate() {
            const invoiceDateValue = invoiceDateInput.value;
            if (!invoiceDateValue) return; // cannot calculate without invoice date

            const invoiceDate = new Date(invoiceDateValue);
            if (isNaN(invoiceDate)) return;

            const termDays = parseInt(paymentTermsSelect.value);

            if (!termDays || termDays === 0) {
                // Pay Upon Receipt → clear due date only if new invoice
                if (!dueDateInput.dataset.existing) dueDateInput.value = '';
            } else {
                invoiceDate.setDate(invoiceDate.getDate() + termDays);
                const formatted = invoiceDate.toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                });
                dueDateInput.value = formatted;
            }
        }

        // Mark if due date already exists
        if (dueDateInput.value) {
            dueDateInput.dataset.existing = true;
        }

        // Initial calculation only if due date is empty
        if (!dueDateInput.value) {
            calculateDueDate();
        }

        // Recalculate when payment term changes
        paymentTermsSelect.addEventListener('change', calculateDueDate);
    });
</script>

@endpush