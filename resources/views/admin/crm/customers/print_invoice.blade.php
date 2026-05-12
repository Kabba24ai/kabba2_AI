<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Invoice - {{ $invoice->invoice_number }}</title>

    <style>
        @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap");
    </style>

</head>

<body style=" font-family: Arial, sans-serif; color:#111;">

       @php
                    $siteLogo = \App\Helpers\ConfigurationHelper::getBrandingLogo();
                    $sitename = \App\Helpers\ConfigurationHelper::getSettings('Website Management Branding','site_name');
                    $primaryStore = \App\Models\Stores\Store::primary()->first();
                    @endphp

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
                                                    <img src="{{ public_path(parse_url($siteLogo, PHP_URL_PATH)) }}" width="48" height="48" alt="Logo" style="display:block; margin:auto;">
                                                </td>
                                                <td style="padding-left:12px; vertical-align:middle;">
                                                    <h3 style="font-weight:600; font-size:20px; margin:0; color:#111;">{{ $sitename ?: "Rent 'n King" }}</h3>
                                                    <p style="margin:0; font-size:13px; color:#111;">Professional Rentals & Sales</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Right: Invoice Info -->
                                    <td style="text-align:right; vertical-align:middle;">
                                        <h3 style="font-weight:700; font-size:20px; margin:0; color:#111;">Invoice</h3>
                                        <p style="margin:4px 0; color:#111;">#{{ $invoice->invoice_number }}</p>
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
                                        <p style="margin:0; color:#000; line-height:1.3;">
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

                                        </p>
                                    </td>
                                    <!-- Right -->
                                    <td style="width:40%; text-align:left; vertical-align:top; ">
                                        <p style="margin:8px 0 4px; font-weight:600; font-size:16px; vertical-align:top;">
                                            <img src="{{ public_path('storage/admin/images/icons/img-2.jpg') }}" width="20" height="20" style="vertical-align:top; margin-right:5px;">
                                            Invoice Date: <span style="margin:0; font-size:15px; font-weight:500;"> {{ \App\Helpers\CustomHelper::formatDate($invoice->invoice_date ?? null) }} </span>
                                        </p>

                                        <p style="margin:8px 0 4px; font-weight:600; font-size:16px; vertical-align:top;">
                                            <img src="{{ public_path('storage/admin/images/icons/img-2.jpg') }}" width="20" height="20" style="vertical-align:top; margin-right:5px;">
                                            Due Date: <span style="margin:0; font-size:15px; font-weight:500;"> {{ $invoice->due_date ? \App\Helpers\CustomHelper::formatDate($invoice->due_date) : 'Pay Upon Invoice' }} </span>
                                        </p>

                                        
                                        
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
                            

                            <table role="presentation" style="width:100%; border-radius:6px;  padding:5px 15px 15px 0px; ">
                                <tr>
                                    <td>
                                        
                                        <table role="presentation" style="width:100%; border-collapse:collapse;">
                                            <tr>
                                                <!-- LEFT SIDE -->
                                                <td style="vertical-align:top;">

                                                    <table role="presentation" style="border-collapse:collapse;">
                                                                                <tr>
                                                                                    <td style="vertical-align:top; padding-right:5px;">
                                                                                        <img src="{{ public_path('storage/admin/images/icons/img-3.png') }}"
                                                                                            width="20" height="20">
                                                                                    </td>

                                                                                    <td style="vertical-align:top; font-weight:600; font-size:15px;">
                                                                                        Bill To :
                                                                                    </td>
                                                                                </tr>
                                                                            </table>

                                                                            


                                                    <table role="presentation" style="margin-top:10px; width:100%; border-collapse:collapse;">
                                                        <tr>
                                                            <!-- LEFT 50% -->
                                                            <td style="width:50%; vertical-align:top; font-size:16px; font-weight:600;">
                                                                {{ $invoice->customer->full_name }}

                                                                @if(!empty($invoice->customer->company_name)) 
                                                                    <span style="color:#000;font-size:14px; font-weight:normal;">
                                                                        ({{ $invoice->customer->company_name }})
                                                                    </span>
                                                                @endif
                                                            </td>

                                                            <!-- RIGHT 50% -->
                                                            <td style="width:50%; text-align:left; vertical-align:top; font-size:14px;">
                                                                <span style="font-weight:600;">Account ID:</span>
                                                                {{ $invoice->customer->unique_id ?? 'N/A' }}
                                                            </td>
                                                        </tr>
                                                    </table>

                                            

                                                </td>

                                            </tr>
                                        </table>
                                       
                                        <!-- Contact info in 2 columns -->
                                        <table role="presentation" style="width:100%; margin-top:8px; border-collapse:collapse;">
                                            <tr>
                                                <!-- Phone -->
                                                <td style="font-size:14px; color:#000; padding:4px 0; vertical-align:top; width:50%;">
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
                                                <td style="font-size:14px; color:#000; padding:4px 0; vertical-align:top;">
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

                                                                        <td style="font-size:14px; color:#000; padding:4px 0; vertical-align:top;">
                                                                            <table role="presentation" style="border-collapse:collapse;">
                                                                                <tr>
                                                                                    <td style="padding-right:5px;  vertical-align:top; ">
                                                                                        <img src="{{ public_path('storage/admin/images/icons/img-6.png') }}" width="17">
                                                                                    </td>
                                                                                    <td style="vertical-align:top;">

                                                                                        <p style="margin:0; font-weight:500; color:#000;">
                                                                                            {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                                                                                        </p>

                                                                                        <div style="line-height:1.4;">

                                                                                            {{-- If Shipping and same as Billing --}}
                                                                                            @if($addressItem['label'] === 'Shipping' && $isSameAddress)
                                                                                                <div>Same as Billing Address</div>
                                                                                            @else
                                                                                            @if($addresse?->address)
                                                                                                <div>{{ $addresse->address }},  @if($addresse?->city)
                                                                                                    {{ $addresse->city }},
                                                                                                @endif </div>
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
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#000;">Id</th>
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#000;">Item</th>
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#000;">PO#</th>
                                        {{-- <th align="center" style="padding:8px 0; font-weight:600; color:#000;">Qty</th> --}}
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#000;">Price</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#000;">Tax</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#000;">Total</th>
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
                                     $total = $item->orderProduct->sub_total + $item->orderProduct->tax ;
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
                                                <div style="font-size:12px; color:#111;"> {{  $item->notes ?? ''}} </div>
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
                                        {{-- <td align="center">{{ $qty }}</td> --}}
                                        <td align="right" style="font-weight:600;color:{{ $textColor }};">
                                            {{-- {{ \App\Helpers\CustomHelper::formatCurrency($rowprice) }} --}}
                                             @if ($item->type === 'order')
                                      {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->sub_total) }}
                                        @else
                                      {{ $prefix }}  {{ \App\Helpers\CustomHelper::formatCurrency($item->unit) }}
                                        @endif
                                        </td>
                             
                                        <td align="right"  style="font-weight:600;color:{{ $textColor }};">{{ $prefix }} {{ \App\Helpers\CustomHelper::formatCurrency($item->tax) }}</td>


                                        <td align="right"  style="font-weight:600;color:{{ $textColor }};">{{ $prefix }} {{ \App\Helpers\CustomHelper::formatCurrency($total ) }}</td>
                                    </tr>

                                    {{-- Rental Items --}}
                                    {{-- @if($item->type === 'order' && $item->orderProduct && $item->orderProduct->product_data)
                                    @foreach ($item->orderProduct->product_data['product_rental_items_prices'] ?? [] as $rentalKey => $rentalPrice)
                                    @php
                                    $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())
                                    ->firstWhere('name', $rentalKey)?->value;
                                    $quantity = $case ? $item->orderProduct->quantity : 1;
                                    @endphp
                                    <tr>
                                        <td></td>
                                        <td style="padding-left:15px; font-size:12px; color:#111; padding-bottom:5px;">
                                            + {{ $case ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                                            <span class="text-xs text-gray-400">(x{{ $quantity }})</span>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#111;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                                        </td>
                                           <td></td>
                                        <td align="right" style="font-size:12px; color:#111;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice * $quantity) }}
                                        </td>
                                    </tr>
                                    @endforeach --}}

                                    

                                      {{-- @if (!empty($item->orderProduct->distance_range))

                                         <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>

                                            <td style="padding-left:15px; font-size:12px; color:#111; padding-bottom:10px;">
                                                + Distance Range
                                                <span class="text-xs text-gray-400">( {{ ucfirst($item->orderProduct->distance_type) }}
                                                            ({{ ucfirst($item->orderProduct->distance_range) }}))</span>
                                            </td>
                                        <td></td>

                                            <td></td>
                                            <td align="right" style="font-size:12px; color:#111;">
                                               

                                                             {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                                            </td>
                                               <td></td>
                                            <td align="right" style="font-size:12px; color:#111;">
                                                 {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }} 
                                            </td>
                                        </tr>

                                        @endif --}}
                                    {{-- Option Items --}}
                                    {{-- @foreach ($item->orderProduct->product_data['product_option_items'] ?? [] as $option)
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>

                                        <td style="padding-left:15px; font-size:12px; color:#111; padding-bottom:10px;">
                                            + {{ $option['name'] }}
                                            <span class="text-xs text-gray-400">(x{{ $item->orderProduct->quantity ?? 1 }})</span>
                                        </td>
                                        <td></td>

                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#111;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                                        </td>
                                           <td></td>
                                        <td align="right" style="font-size:12px; color:#111;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency(($option['price'] ?? 0) * ($item->orderProduct->quantity ?? 1)) }}
                                        </td>
                                    </tr>
                                    @endforeach --}}
                                    {{-- @endif --}}

                                    {{-- Divider --}}
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <td></td>
                                        <td></td>

                                        {{-- <td></td> --}}
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


                    <tr>
                        <td style="padding-top:20px;">
                            <table role="presentation" width="100%" style="font-size:14px; border-collapse:collapse;">
                                
                                <tr>
                                    <td style="text-align:right; padding:4px 0;">
                                        Subtotal:
                                    </td>
                                    <td style="text-align:right; padding:4px 0; width:90px;">
                                        {{ \App\Helpers\CustomHelper::formatCurrency($invoice->subtotal) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="text-align:right; padding:4px 0;">
                                        Tax ({{ \App\Helpers\CustomHelper::displayPercentage($sales_tax) }}%):
                                    </td>
                                    <td style="text-align:right; padding:4px 0;">
                                        {{ \App\Helpers\CustomHelper::formatCurrency($invoice->sales_tax) }}
                                    </td>
                                </tr>

                                @if($discountTotal > 0)
                                <tr>
                                    <td style="text-align:right; padding:4px 0; color:green;">
                                        Discount:
                                    </td>
                                    <td style="text-align:right; padding:4px 0; color:green;">
                                        -{{ \App\Helpers\CustomHelper::formatCurrency($discountTotal) }}
                                    </td>
                                </tr>
                                @endif

                                @if($refundTotal > 0)
                                <tr>
                                    <td style="text-align:right; padding:4px 0; color:green;">
                                        Refund:
                                    </td>
                                    <td style="text-align:right; padding:4px 0; color:green;">
                                        -{{ \App\Helpers\CustomHelper::formatCurrency($refundTotal) }}
                                    </td>
                                </tr>
                                @endif

                                <!-- TOTAL -->
                                <tr>
                                    <td colspan="2" style="border-top:1px solid #111827; padding-top:10px;"></td>
                                </tr>

                                <tr>
                                    <td style="text-align:right; font-weight:700; font-size:16px;">
                                        Total:
                                    </td>
                                    <td style="text-align:right; font-weight:700; font-size:16px;">
                                        {{ \App\Helpers\CustomHelper::formatCurrency($invoice->total) }}
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="text-align:center; padding:30px 0 0; border-top:1px solid #111827; font-size:13px; color:#111;">

                              @if(!empty($invoice->invoice_notes))
                                    <p style="margin:6px 0px 10px 0px;">
                                        {{ $invoice->invoice_notes }}
                                    </p>
                                @endif

                            <p  style="margin:0;">Thank you for your business!</p>

                            <p  style="margin:0;">*Order details can be found In your Customer Portal</p>

                                @php
                                    $supportPhone = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_phone');
                                @endphp

                                @if(!empty($supportPhone))
                                    <p style="margin:0;">
                                        For questions about this Invoice, contact us at {{ $supportPhone }}
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
