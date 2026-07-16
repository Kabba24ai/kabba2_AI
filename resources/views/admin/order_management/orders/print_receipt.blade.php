<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Receipt - #{{ $receipt->order_id }}</title>

</head>

<body style=" font-family: Arial, sans-serif; color:#111;">
    <table role="presentation" style="width:100%; border-collapse:collapse;">
                    @php
                    $siteLogo = \App\Helpers\ConfigurationHelper::getBrandingLogo();
                    $sitename = \App\Helpers\ConfigurationHelper::getSettings('Website Management Branding','site_name');
                    $primaryStore = \App\Models\Stores\Store::primary()->first();
                    @endphp
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
                                                    <h3 style="font-weight:600; font-size:20px; margin:0; color:#111;"> {{ $sitename ?: "Rent 'n King" }}</h3>
                                                    <p style="margin:0; font-size:13px; color:#000;">Professional Rentals & Sales</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Right: Receipt Info -->
                                    <td style="text-align:right; vertical-align:middle;">
                                        <h3 style="font-weight:700; font-size:20px; margin:0; color:#111;">RECEIPT</h3>
                                        <p style="margin:4px 0; color:#000;">Order ID: #{{ $receipt->order_id }}</p>
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
                                        <p style="font-weight:600; font-size:16px; margin:0 0 6px; color:#000;">From:</p>
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
                                        <p style="margin:0px 0 4px; font-weight:600; font-size:16px;">
                                            {{-- <img src="{{ public_path('storage/admin/images/icons/img-2.jpg') }}" width="20" height="20" style="vertical-align:middle; margin-right:5px;"> --}}
                                            Order Date: <span style="margin:0; font-size:15px; font-weight:500;">
                                                {{ \App\Helpers\CustomHelper::formatDate($receipt->order_date ?? null) }}

                                            </span>
                                        </p>

                                        <p style="margin:12px 0 4px; font-weight:600; font-size:16px;">Customer PO: <span style="margin:0; font-size:15px; font-weight:500;"> {{ $order->po_id ?? '-' }} </span></p>

                                            {{-- <p style="margin:16px 0 4px; font-weight:600; font-size:16px;">Customer PO: <span style="margin:0; font-size:14px; color:#374151; font-weight:500;">{{ $order->po_id }}</span></p> --}}

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
                        <td>


                                                            <table role="presentation" style="width:100%; border-collapse:collapse; margin-top:10px;">
                                                                <tr>
                                                                    <!-- LEFT SIDE -->
                                                                    <td style="vertical-align:top;">

                                                                        {{-- <img src="{{ public_path('storage/admin/images/icons/img-3.png') }}"
                                                                            width="20" height="20"
                                                                            style="vertical-align:middle; margin-right:5px;">

                                                                        <span style="font-weight:600; font-size:15px; ">Bill To :</span><br> <br> --}}


                                                                            <table role="presentation" style="border-collapse:collapse; margin-bottom:15px;">
                                                                                <tr>
                                                                                    <td style="vertical-align:top; ">
                                                                                        <img src="{{ public_path('storage/admin/images/icons/img-3.png') }}"
                                                                                            width="20" height="20">
                                                                                    </td>

                                                                                    <td style="vertical-align:top; font-weight:600; font-size:15px;">
                                                                                        Bill To :
                                                                                    </td>
                                                                                </tr>
                                                                            </table>


                                                                        {{-- <span style="font-size:16px; font-weight:600; width:100%" >
                                                                            {{ $invoice->customer->full_name }}
                                                                        </span> --}}


                                                                        <table role="presentation" style="width:100%; border-collapse:collapse;">
                                                                            <tr>
                                                                                <!-- LEFT 50% -->
                                                                                <td style="width:50%; vertical-align:top; font-size:16px; font-weight:600;">
                                                                                    {{ $receipt->customer->full_name }}

                                                                                    @if(!empty($receipt->customer->company_name))
                                                                                        <span style="color:#000; font-weight:normal;font-size:15px;">
                                                                                            ({{ $receipt->customer->company_name }})
                                                                                        </span>
                                                                                    @endif
                                                                                </td>

                                                                                <!-- RIGHT 50% -->
                                                                                <td style="width:50%; text-align:left; vertical-align:top; font-size:14px;">
                                                                                    <span style="font-weight:600;">Account ID:</span>
                                                                                    {{ $receipt->customer->unique_id ?? 'N/A' }}
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
                                                                                    {{ \App\Helpers\CustomHelper::formatPhone($receipt->customer->phone ?? '') ?: 'N/A' }}
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
                                                                                    {{ $receipt->customer->email ?? 'N/A' }}
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




                    <!-- Items Table -->
                    <tr>
                        <td>
                            <table role="presentation" style="width:100%; border-collapse:collapse; font-size:14px;">
                                <thead>
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <th align="left" style="padding:8px 0; font-weight:600; color:#000;">Item</th>
                                        <th align="center" style="padding:8px 0; font-weight:600; color:#000;">Qty</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#000;">Unit Price</th>
                                        <th align="right" style="padding:8px 0; font-weight:600; color:#000;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($receipt->items as $item)
                                    {{-- Main Item Row --}}
                                    @php
                                    if ($item->type === 'order') {
                                    $rowprice = $item->orderProduct->price;
                                    $qty = $item->orderProduct->quantity ?? 1;
                                    } else {
                                    $rowprice = $item->unit;
                                    $qty = $item->qty ?? 1;
                                    }
                                    $total = $rowprice * $qty;
                                    @endphp
                                    <tr>
                                        <td style="padding:8px 0; font-weight:500;">{{ $item->item_name }}</td>
                                        <td align="center">{{ $qty }}</td>
                                        <td align="right">{{ \App\Helpers\CustomHelper::formatCurrency($rowprice) }}</td>
                                        <td align="right" style="font-weight:600;">{{ \App\Helpers\CustomHelper::formatCurrency($total) }}</td>
                                    </tr>

                                    {{-- Rental Items --}}
                                    @if($item->type === 'order' && $item->orderProduct && $item->orderProduct->product_data)
                                    @foreach ($item->orderProduct->product_data['product_rental_items_prices'] ?? [] as $rentalKey => $rentalPrice)
                                    @php
                                    $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())
                                    ->firstWhere('name', $rentalKey);
                                    $quantity = $case ? $item->orderProduct->quantity : 1;
                                    @endphp
                                    <tr>
                                        <td style="padding-left:15px; font-size:12px; color:#000; padding-bottom:5px;">
                                            + {{ $case?->label() ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                                            <span class="text-xs text-gray-400">(x{{ $quantity }})</span>
                                        </td>
                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#000;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                                        </td>
                                        <td align="right" style="font-size:12px; color:#000;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice * $quantity) }}
                                        </td>
                                    </tr>
                                    @endforeach

                                         @if (!empty($item->orderProduct->distance_range))

                                         <tr style="border-bottom:1px solid #d1d5db;">
                                            <td style="padding-left:15px; font-size:12px; color:#000; padding-bottom:10px;">
                                                + Distance Range
                                                <span class="text-xs text-gray-400">( {{ ucfirst($item->orderProduct->distance_type) }}
                                                            ({{ ucfirst($item->orderProduct->distance_range) }}))</span>
                                            </td>
                                            <td></td>
                                            <td align="right" style="font-size:12px; color:#000;">


                                                             {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }}
                                            </td>
                                            <td align="right" style="font-size:12px; color:#000;">
                                                 {{ \App\Helpers\CustomHelper::formatCurrency($item->orderProduct->product_data['service_option_price'] ?? 0) }}
                                            </td>
                                        </tr>

                                        @endif

                                    {{-- Option Items --}}
                                    @foreach ($item->orderProduct->product_data['product_option_items'] ?? [] as $option)
                                    <tr style="border-bottom:1px solid #d1d5db;">
                                        <td style="padding-left:15px; font-size:12px; color:#000; padding-bottom:10px;">
                                            + {{ $option['name'] }}
                                            <span class="text-xs text-gray-400">(x{{ $item->orderProduct->quantity ?? 1 }})</span>
                                        </td>
                                        <td></td>
                                        <td align="right" style="font-size:12px; color:#000;">
                                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                                        </td>
                                        <td align="right" style="font-size:12px; color:#000;">
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


                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:5px;">
                            <div style="position:relative; width:100%; display:block;">



                                <!-- Text -->
                                <div style="position:relative; font-weight:600; z-index:10;">
                                    Payment Status: {{ \App\Services\ReceiptService::currentPaymentStatusLabel($order) }}
                                </div>

                                @php
                                    $receiptMethodLabel = \App\Services\ReceiptService::currentPaymentMethodLabel($order);
                                    $receiptTermsLabel = \App\Services\PaymentDescriptionPresenter::termsLabel($order);
                                @endphp
                                @if ($receiptMethodLabel)
                                    <div style="position:relative; font-weight:600; z-index:10; margin-top:4px;">
                                        Payment Method: {{ $receiptMethodLabel }}
                                    </div>
                                @elseif ($receiptTermsLabel)
                                    <div style="position:relative; font-weight:600; z-index:10; margin-top:4px;">
                                        Payment Terms: {{ $receiptTermsLabel }}
                                    </div>
                                @endif

                            </div>
                        </td>
                    </tr>



                    <tr>
    <td style="padding-top:20px;">
        <table role="presentation" width="100%" style="font-size:14px; border-collapse:collapse;">

            <tr>
                <td style="text-align:right; padding:4px 0;">
                    Subtotal :
                </td>
                <td style="text-align:right; padding:4px 0; width:110px;">
                    {{ \App\Helpers\CustomHelper::formatCurrency($receipt->subtotal) }}
                </td>
            </tr>

            <tr>
                <td style="text-align:right; padding:4px 0;">
                    Tax ( {{ \App\Helpers\CustomHelper::displayPercentage($sales_tax) }} %) :
                </td>
                <td style="text-align:right; padding:4px 0;">
                    {{ \App\Helpers\CustomHelper::formatCurrency($receipt->sales_tax) }}
                </td>
            </tr>



            <!-- TOTAL -->
            <tr>
                <td colspan="2" style="border-top:1px solid #111827; padding-top:10px;"></td>
            </tr>

            <tr>
                <td style="text-align:right; font-weight:700; font-size:16px;">
                    Order Total :
                </td>
                <td style="text-align:right; font-weight:700; font-size:16px;">
                   {{ \App\Helpers\CustomHelper::formatCurrency($receipt->total) }}
                </td>
            </tr>

            {{-- Phase 3D — Payment/refund summary (mission §5). Only shown
                 once money has actually moved either way, so a brand-new,
                 unpaid receipt is unaffected. Sourced from OrderPaymentSummary
                 (already built, never wired into this view before) — never
                 recomputed here. --}}
            @php
                $receiptPaymentSummary = \App\Services\Orders\OrderPaymentSummary::for($order);
                $receiptRefundDetails = \App\Services\ReceiptService::refundDetails($order);
            @endphp
            @if ($receiptPaymentSummary->totalSettledPayments > 0 || $receiptPaymentSummary->totalRefunded > 0)
                <tr>
                    <td colspan="2" style="padding-top:10px;"></td>
                </tr>
                <tr>
                    <td style="text-align:right; padding:4px 0;">Payments Received :</td>
                    <td style="text-align:right; padding:4px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptPaymentSummary->totalSettledPayments) }}</td>
                </tr>
                @if ($receiptPaymentSummary->totalRefunded > 0)
                    <tr>
                        <td style="text-align:right; padding:4px 0;">Refunds Completed :</td>
                        <td style="text-align:right; padding:4px 0;">-{{ \App\Helpers\CustomHelper::formatCurrency($receiptPaymentSummary->totalRefunded) }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="text-align:right; font-weight:700; padding:4px 0;">Net Paid :</td>
                    <td style="text-align:right; font-weight:700; padding:4px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptPaymentSummary->netPaid) }}</td>
                </tr>
            @endif

        </table>
    </td>
</tr>

                    {{-- Phase 3D — itemized refund list (mission §5). Only
                         successful (Allocated) allocations ever reach here
                         (see ReceiptService::refundDetails()) — pending or
                         failed attempts never appear as a completed refund
                         on a customer-facing receipt, and no gateway id or
                         internal failure detail is exposed. Mixed payment
                         methods render as separate lines. --}}
                    @if (!empty($receiptRefundDetails))
                        <tr>
                            <td style="padding-top:20px;">
                                <table role="presentation" width="100%" style="font-size:14px; border-collapse:collapse;">
                                    <tr>
                                        <td colspan="2" style="font-weight:700; padding-bottom:6px; border-bottom:1px solid #111827;">Refunds</td>
                                    </tr>
                                    @foreach ($receiptRefundDetails as $line)
                                        <tr>
                                            <td style="padding:6px 0 0;">
                                                {{ $line['method'] }}<br>
                                                <span style="color:#6b7280; font-size:12px;">{{ $line['date']?->format('F j, Y') }}</span>
                                            </td>
                                            <td style="text-align:right; padding:6px 0 0; vertical-align:top;">
                                                {{ \App\Helpers\CustomHelper::formatCurrency($line['amount']) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    @php
                                        $receiptFeeLines = collect($receiptRefundDetails)->where('calc_type', \App\Enums\Orders\RefundCalculationType::CardProcessingFeeRetained->value);
                                        $receiptTaxOnlyLines = collect($receiptRefundDetails)->where('calc_type', \App\Enums\Orders\RefundCalculationType::SalesTaxOnly->value);
                                    @endphp

                                    @if ($receiptFeeLines->isNotEmpty())
                                        @php
                                            $receiptFeeTotal = $receiptFeeLines->sum('fee_retained');
                                            $receiptFeeGross = $receiptFeeLines->sum(fn ($l) => $l['amount'] + $l['fee_retained']);
                                        @endphp
                                        <tr><td colspan="2" style="padding-top:10px;"></td></tr>
                                        <tr>
                                            <td style="text-align:right; padding:2px 0;">Refundable Amount :</td>
                                            <td style="text-align:right; padding:2px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptFeeGross) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right; padding:2px 0;">Card Processing Fee Retained :</td>
                                            <td style="text-align:right; padding:2px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptFeeTotal) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right; font-weight:700; padding:2px 0;">Customer Refund :</td>
                                            <td style="text-align:right; font-weight:700; padding:2px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptFeeGross - $receiptFeeTotal) }}</td>
                                        </tr>
                                    @endif

                                    @if ($receiptTaxOnlyLines->isNotEmpty())
                                        <tr><td colspan="2" style="padding-top:10px;"></td></tr>
                                        <tr>
                                            <td style="text-align:right; padding:2px 0;">Sales Tax Refund :</td>
                                            <td style="text-align:right; padding:2px 0;">{{ \App\Helpers\CustomHelper::formatCurrency($receiptTaxOnlyLines->sum('amount')) }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td style="text-align:center; padding:30px 0 0; border-top:1px solid #111827; font-size:13px; color:#000;">
                            <p style="margin:6px 0;">Thank you for your business!</p>
                          @php
                                $supportPhone = \App\Helpers\ConfigurationHelper::getSettings(null, 'invoice_phone');
                            @endphp

                            @if(!empty($supportPhone))
                                <p style="margin:0;">
                                    For questions about this receipt, contact us at {{ $supportPhone }}
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
