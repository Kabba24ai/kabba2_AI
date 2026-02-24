<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Receipt - {{ $invoice->invoice_number }}</title>

    <style>
        @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap");
    </style>

</head>

<body style=" font-family: Arial, sans-serif; color:#111;">
    <table role="presentation" style="width:100%; border-collapse:collapse;">

        <tr>

            <td align="center">
                <!-- Card -->
                <table role="presentation" style="width:100%; max-width:800px; background:#ffffff; border-radius:6px; box-shadow:0 2px 5px rgba(0,0,0,0.1); padding:30px;">


                    <!-- Header -->
                    <tr>
                        <td style="padding-bottom:20px;">
                            <table role="presentation" style="width:100%; border-collapse:collapse;">
                                <tr>
                                    <!-- Left: Logo + Name -->
                                    <td style="text-align:left; vertical-align:middle;">
                                        <table role="presentation" style="border-collapse:collapse;">
                                            <tr>
                                                <td>
                                                    <img src="{{ public_path('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" width="48" height="48" alt="Logo" style="display:block; margin:auto;">
                                                </td>
                                                <td style="padding-left:12px; vertical-align:middle;">
                                                    <h3 style="font-weight:600; font-size:20px; margin:0; color:#111;">Rent 'n King</h3>
                                                    <p style="margin:0; font-size:13px; color:#6b7280;">Professional Rentals & Sales</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Right: Receipt Info -->
                                    <td style="text-align:right; vertical-align:middle;">
                                        <h3 style="font-weight:700; font-size:20px; margin:0; color:#111;">RECEIPT</h3>
                                        <p style="margin:4px 0; color:#6b7280;">#{{ $invoice->invoice_number }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- From + Transaction Section -->
                    <tr>
                        <td style="padding-bottom:20px; border-bottom:2px solid #111827;">
                            <table role="presentation" style="width:100%; border-collapse:collapse;">
                                <tr>
                                    <!-- Left -->
                                    <td style="width:60%; vertical-align:top;  font-size:14px;">
                                        <h3 style="font-weight:600; font-size:16px; margin:0 0 6px; color:#000;">From:</h3>
                                        <p style="margin:0; color:#374151;">
                                            Rent 'n King .<br>
                                            10296 Highway 46<br>
                                            Bon Aqua, TN 37025<br>
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

                                        </p>
                                    </td>
                                    <!-- Right -->
                                    <td style="width:40%; text-align:left; vertical-align:top; ">
                                        <p style="margin:8px 0 4px; font-weight:600; font-size:16px;">
                                            <img src="{{ public_path('storage/admin/images/icons/img-2.jpg') }}" width="20" height="20" style="vertical-align:middle; margin-right:5px;">
                                            Invoice Date: <span style="margin:0; font-size:15px; font-weight:500;"> {{ \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? null) }} </span>
                                        </p>

                                        <p style="margin:8px 0 4px; font-weight:600; font-size:16px;">
                                            <img src="{{ public_path('storage/admin/images/icons/img-2.jpg') }}" width="20" height="20" style="vertical-align:middle; margin-right:5px;">
                                            Due Date: <span style="margin:0; font-size:15px; font-weight:500;"> {{ $invoice->due_date ? \App\Helpers\CustomHelper::formatDate($invoice->due_date) : 'Pay Upon Receipt' }} </span>
                                        </p>

                                        
                                            <p style="margin:16px 0 4px; font-weight:600; font-size:16px;">Account: <span style="margin:0; font-size:14px; color:#374151; font-weight:500;">{{ $invoice->customer->unique_id }}</span></p>
                                            
                                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @php
                    $billingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Billing' );
                    $shippingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Shipping');

                    $defaultAddresses = [
                    ['label' => 'Billing', 'data' => $billingAddress],
                    ['label' => 'Shipping', 'data' => $shippingAddress],
                    ];
                    @endphp

                    <!-- Bill To -->
                    <tr>
                        <td >
                            

                            <table role="presentation" style="width:100%; border-radius:6px; background:#f9fafb; padding:15px; padding-top:0px;">
                                <tr>
                                    <td>
                                        
                            <p> 
                                <img src="{{ public_path('storage/admin/images/icons/img-3.png') }}" width="20" height="20" style="vertical-align:middle; margin-right:5px;">

                                <span style="margin:0 0 10px; font-weight:600; font-size:15px;">Bill To:-</span>
                                <span style="margin:0; font-size:16px; font-weight:600;">{{ $invoice->customer->full_name }}</span>
                                  @if(!empty($invoice->customer->company_name)) 
                                <span style="margin:0; color:#374151;">({{ $invoice->customer->company_name }})</span>
                                @endif

                            </p>
                                       
                                        <!-- Contact info in 2 columns -->
                                        <table role="presentation" style="width:100%; margin-top:8px; border-collapse:collapse;">
                                            <tr>
                                                <!-- Phone -->
                                                <td style="font-size:14px; color:#374151; padding:4px 0; vertical-align:top; width:50%;">
                                                    <table role="presentation" style="border-collapse:collapse;">
                                                        <tr>
                                                            <td style="padding-right:5px;">
                                                                <img src="{{ public_path('storage/admin/images/icons/img-4.png') }}" width="17" height="17">
                                                            </td>
                                                            <td>
                                                                {{ \App\Helpers\CustomHelper::formatPhone($invoice->customer->phone ?? '') ?: 'N/A' }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>

                                                <!-- Email -->
                                                <td style="font-size:14px; color:#374151; padding:4px 0; vertical-align:top;">
                                                    <table role="presentation" style="border-collapse:collapse;">
                                                        <tr>
                                                            <td style="padding-right:5px;">
                                                                <img src="{{ public_path('storage/admin/images/icons/img-5.png') }}" width="17" height="17">
                                                            </td>
                                                            <td>
                                                                {{ $invoice->customer->email ?? 'N/A' }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
<tr>

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

        <td style="font-size:14px; color:#374151; padding:4px 0; vertical-align:top;">
            <table role="presentation" style="border-collapse:collapse;">
                <tr>
                    <td style="padding-right:5px;">
                        <img src="{{ public_path('storage/admin/images/icons/img-6.png') }}" width="17">
                    </td>
                    <td>

                        <p style="margin:0; font-weight:500; color:#000;">
                            {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                        </p>

                        <div style="line-height:1.4;">

                            {{-- If Shipping and same as Billing --}}
                            @if($addressItem['label'] === 'Shipping' && $isSameAddress)
                                <div>Same as Billing Address</div>
                            @else
                                @if($addresse?->address)
                                    <div>{{ $addresse->address }},</div>
                                @endif

                                <div>
                                    @if($addresse?->city)
                                        {{ $addresse->city }},
                                    @endif
                                    @if($addresse?->state?->name)
                                        {{ $addresse->state->name }}
                                    @endif
                                    @if($addresse?->zip_code)
                                        {{ ' ' . $addresse->zip_code }}
                                    @endif
                                </div>
                            @endif

                        </div>

                    </td>
                </tr>
            </table>
        </td>

    @endforeach

</tr>

                                        </table>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>


                    <!-- Items Table -->
                    <tr>
                        <td>
                            <table role="presentation" style="width:100%; border-collapse:collapse; font-size:14px;">
                                <thead>
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#374151;">Id</th>
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#374151;">Item</th>
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#374151;">PO#</th>
                                        <th align="center" style="padding:8px 0; font-weight:600; color:#374151;">Qty</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#374151;">Unit Price</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#374151;">Tax</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#374151;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                    {{-- Main Item Row --}}
                                    @php
                                    if ($item->type === 'order') {
                                    $rowprice = $item->orderProduct->price ;
                                    $qty = $item->orderProduct->quantity ?? 1;

                                     $total_without_data = $rowprice * $qty;
                                     $total = $total_without_data + $item->orderProduct->tax ;
                                    } else {
                                    $rowprice = $item->total;
                                    $qty = $item->qty ?? 1;
                                     $total = $rowprice * $qty;
                                    }
                                   
                                    @endphp
                                    <tr>
                                        <td align="left">   
                                                            @if ($item->type === 'order')
                                                            #{{ $item->orderProduct->order->id }}
                                                            @else
                                                            -
                                                            @endif
                                                        </td>
                                                        <td style="padding:8px 0; font-weight:500;">{{ $item->item_name }} 
                                                <div style="font-size:12px; color:#6b7280;"> {{  $item->notes ?? ''}} </div>
                                        </td>

                                        <td align="left">   
                                            @if ($item->type === 'order')
                                            {{  $item->orderProduct->order->po_id ?? '-'}}
                                            @else
                                            -
                                            @endif
                                        </td>
                                        @php
                                            
                                            $isAdjustment = in_array($item->type, ['discount', 'refund']);

                                            $textColor = $isAdjustment ? 'green' : '';
                                            $prefix = $isAdjustment ? '-' : '';
                                        @endphp
                                        <td align="center">{{ $qty }}</td>
                                        <td align="right" style="font-weight:600;color:{{ $textColor }};">
                                            {{-- {{ \App\Helpers\CustomHelper::formatCurrency($rowprice) }} --}}
                                             @if ($item->type === 'order')
                                      {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->price) }}
                                        @else
                                      {{ $prefix }}  {{ \App\Helpers\CustomHelper::formatCurrency($item->unit) }}
                                        @endif
                                        </td>
                             
                                        <td align="right"  style="font-weight:600;color:{{ $textColor }};">{{ $prefix }} {{ \App\Helpers\CustomHelper::formatCurrency($item->tax) }}</td>


                                        <td align="right"  style="font-weight:600;color:{{ $textColor }};">{{ $prefix }} {{ \App\Helpers\CustomHelper::formatCurrency($total ) }}</td>
                                    </tr>

                                    {{-- Rental Items --}}
                                    @if($item->type === 'order' && $item->orderProduct && $item->orderProduct->product_data)
                                    @foreach ($item->orderProduct->product_data['product_rental_items_prices'] ?? [] as $rentalKey => $rentalPrice)
                                    @php
                                    $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())
                                    ->firstWhere('name', $rentalKey)?->value;
                                    $quantity = $case ? $item->orderProduct->quantity : 1;
                                    @endphp
                                    <tr>
                                        <td></td>
                                        <td style="padding-left:15px; font-size:12px; color:#6b7280; padding-bottom:5px;">
                                            + {{ $case ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                                            <span class="text-xs text-gray-400">(x{{ $quantity }})</span>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#6b7280;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                                        </td>
                                           <td></td>
                                        <td align="right" style="font-size:12px; color:#6b7280;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice * $quantity) }}
                                        </td>
                                    </tr>
                                    @endforeach

                                    

                                      @if (!empty($item->orderProduct->distance_range))

                                         <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>

                                            <td style="padding-left:15px; font-size:12px; color:#6b7280; padding-bottom:10px;">
                                                + Distance Range
                                                <span class="text-xs text-gray-400">( {{ ucfirst($item->orderProduct->distance_type) }}
                                                            ({{ ucfirst($item->orderProduct->distance_range) }}))</span>
                                            </td>
                                        <td></td>

                                            <td></td>
                                            <td align="right" style="font-size:12px; color:#6b7280;">
                                               

                                                             {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                                            </td>
                                               <td></td>
                                            <td align="right" style="font-size:12px; color:#6b7280;">
                                                 {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                                            </td>
                                        </tr>

                                        @endif
                                    {{-- Option Items --}}
                                    @foreach ($item->orderProduct->product_data['product_option_items'] ?? [] as $option)
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>

                                        <td style="padding-left:15px; font-size:12px; color:#6b7280; padding-bottom:10px;">
                                            + {{ $option['name'] }}
                                            <span class="text-xs text-gray-400">(x{{ $item->orderProduct->quantity ?? 1 }})</span>
                                        </td>
                                        <td></td>

                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#6b7280;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                                        </td>
                                           <td></td>
                                        <td align="right" style="font-size:12px; color:#6b7280;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency(($option['price'] ?? 0) * ($item->orderProduct->quantity ?? 1)) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif

                                    {{-- Divider --}}
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>
                                        <td></td>

                                        <td></td>
                                        <td></td>
                                        <td></td>
                                           <td></td>
                                        <td></td>


                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

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



                    <!-- Totals -->
                    <tr>
                        <td style="padding-top:20px;">
                            <table role="presentation" style="width:100%; font-size:14px;">
                                <tr>
                                    <td align="left">Subtotal:</td>
                                    <td align="right">{{ \App\Helpers\CustomHelper::formatCurrency($invoice->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td align="left">Tax ( {{ \App\Helpers\CustomHelper::displayPercentage($sales_tax) }} %):</td>
                                    <td align="right">{{ \App\Helpers\CustomHelper::formatCurrency($invoice->sales_tax) }}</td>
                                </tr>

                                    {{-- Show Discount --}}
                                @if($discountTotal > 0)
            
                                 <tr>
                                    <td align="left" style="color: green">Discount:</td>
                                    <td align="right" style="color: green">-{{ \App\Helpers\CustomHelper::formatCurrency($discountTotal) }}</td>
                                </tr>
                                @endif

                                 @if($refundTotal > 0)
            
                                 <tr>
                                    <td align="left" style="color: green">Refund:</td>
                                    <td align="right" style="color: green">-{{ \App\Helpers\CustomHelper::formatCurrency($refundTotal) }}</td>
                                </tr>
                                @endif

                                <tr style="border-top:2px solid #111827;">
                                    <td align="left" style="padding-top:8px; font-weight:700; font-size:16px;">Total:</td>
                                    <td align="right" style="padding-top:8px; font-weight:700; font-size:16px;">{{ \App\Helpers\CustomHelper::formatCurrency($invoice->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="text-align:center; padding:30px 0 0; border-top:1px solid #111827; font-size:13px; color:#6b7280;">
                            <p style="margin:6px 0;">Thank you for your business!</p>
@php
    $supportPhone = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_phone');
@endphp

@if(!empty($supportPhone))
    <p style="margin:0;">
        For questions about this receipt, contact us at {{ $supportPhone }}
    </p>
@endif

@if(!empty($invoice->invoice_notes))
    <p style="margin:0;">
         {{ $invoice->invoice_notes }}
    </p>
@endif

                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
