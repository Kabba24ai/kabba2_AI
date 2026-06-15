@php
    $damageStatusBadge = fn(?string $status): array => match($status) {
        'resolved'      => ['label' => 'Resolved',      'class' => 'bg-green-50 text-green-700 border-green-200'],
        'completed'     => ['label' => 'Completed',     'class' => 'bg-green-50 text-green-700 border-green-200'],
        'uncollectible' => ['label' => 'Uncollectible', 'class' => 'bg-gray-100 text-gray-500 border-gray-200'],
        default         => ['label' => 'Active',        'class' => 'bg-red-50 text-red-700 border-red-200'],
    };
@endphp

@php
    $crmDamageRecords = $crmDamageRecords ?? collect();
    $hasAny = $records->isNotEmpty() || $crmDamageRecords->isNotEmpty();
@endphp

@if(!$hasAny)
    <div class="text-center py-16 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="mx-auto w-10 h-10 mb-3 opacity-40">
            <path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/>
        </svg>
        <p class="text-sm">No damage alert records found.</p>
    </div>
@else
    <div class="space-y-3">

        {{-- Rental-checklist damage charges (order_products) --}}
        @foreach($records as $record)
            @php
                $order     = $record->order;
                $customer  = $order?->customer;
                $equipment = $record->equipment;

                $baseDamage        = (float) ($record->damage_charge ?? 0);
                $damageAdjustments = $record->damageChargeLogs->sum('change_amount');
                $currentDamage     = max(0, $baseDamage + $damageAdjustments);

                $badge = $damageStatusBadge($record->damage_status);
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition">
                <div class="flex justify-between gap-4">

                    <div class="flex-1 min-w-0">

                        <div class="flex items-center gap-2 flex-wrap">

                            <span class="text-sm font-medium text-gray-500">Customer:</span>
                            <h3 class="text-sm font-semibold text-gray-900">
                                {{ $customer?->full_name ?? '—' }}
                            </h3>

                            @if($customer?->phone)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                    {{ \App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold border {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>

                        </div>

                        <div class="mt-3 border-l-4 border-red-200 bg-red-50/40 rounded-r-lg p-3">
                            <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">

                                <span>
                                    <span class="font-semibold text-gray-700">Order:</span>
                                    @if($order)
                                        <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                           target="_blank"
                                           class="text-blue-600 hover:underline font-medium">
                                            {{ $order->order_number ?? $order->unique_id }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </span>

                                @if($equipment)
                                    <span>
                                        <span class="font-semibold text-gray-700">Equipment:</span>
                                        <span class="text-gray-600">{{ $equipment->equipment_name }}</span>
                                    </span>
                                @endif

                                <span>
                                    <span class="font-semibold text-gray-700">Damage Charge:</span>
                                    <span class="font-semibold text-red-700">
                                        {{ $currentDamage > 0 ? '$' . number_format($currentDamage, 2) : 'Pending' }}
                                    </span>
                                </span>

                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                {{ \App\Helpers\CustomHelper::formatDateTime($record->created_at) }}
                            </span>
                        </div>

                    </div>

                    <div class="flex flex-col items-end justify-center border-l border-gray-100 pl-4 shrink-0 min-w-[90px]">
                        <div class="text-lg font-bold text-red-600">
                            {{ $currentDamage > 0 ? '$' . number_format($currentDamage, 2) : '—' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Damage Charge</div>
                    </div>

                </div>
            </div>
        @endforeach

        {{-- Manually-added damage charges (customer_accounts / CRM / Dashboard) --}}
        @foreach($crmDamageRecords as $crm)
            @php
                $crmCustomer = $crm->customer;
                $crmBase     = (float) ($crm->amount ?? 0);
                $crmTaxRate  = (float) ($crm->sales_tax ?? 0);
                $crmTotal    = ($crm->sales_tax_type === 'add' && $crmTaxRate > 0)
                    ? $crmBase + $crmBase * $crmTaxRate
                    : $crmBase;
                $crmBadge    = $damageStatusBadge($crm->damage_alert_status);
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition">
                <div class="flex justify-between gap-4">

                    <div class="flex-1 min-w-0">

                        <div class="flex items-center gap-2 flex-wrap">

                            <span class="text-sm font-medium text-gray-500">Customer:</span>
                            <h3 class="text-sm font-semibold text-gray-900">
                                {{ $crmCustomer?->full_name ?? '—' }}
                            </h3>

                            @if($crmCustomer?->phone)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                    {{ \App\Helpers\CustomHelper::formatPhone($crmCustomer->phone) }}
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold border {{ $crmBadge['class'] }}">
                                {{ $crmBadge['label'] }}
                            </span>

                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                Manual
                            </span>

                        </div>

                        <div class="mt-3 border-l-4 border-red-200 bg-red-50/40 rounded-r-lg p-3">
                            <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">

                                @if($crm->order_id && $crm->order)
                                    <span>
                                        <span class="font-semibold text-gray-700">Order:</span>
                                        <a href="{{ route('admin.order-management.orders.edit', $crm->order->unique_id) }}"
                                           target="_blank"
                                           class="text-blue-600 hover:underline font-medium">
                                            {{ $crm->order->order_number ?? $crm->order->unique_id }}
                                        </a>
                                    </span>
                                @else
                                    <span>
                                        <span class="font-semibold text-gray-700">Source:</span>
                                        @if($crmCustomer)
                                            <a href="{{ route('admin.crm.customers.view', $crmCustomer->unique_id) }}"
                                               target="_blank"
                                               class="text-blue-600 hover:underline font-medium">
                                                CRM / Credit Balance
                                            </a>
                                        @else
                                            <span class="text-gray-500">CRM / Credit Balance</span>
                                        @endif
                                    </span>
                                @endif

                                <span>
                                    <span class="font-semibold text-gray-700">Damage Charge:</span>
                                    <span class="font-semibold text-red-700">
                                        {{ $crmTotal > 0 ? '$' . number_format($crmTotal, 2) : 'Pending' }}
                                    </span>
                                </span>

                                @if($crm->notes)
                                    <span>
                                        <span class="font-semibold text-gray-700">Note:</span>
                                        <span class="text-gray-600">{{ $crm->notes }}</span>
                                    </span>
                                @endif

                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                {{ \App\Helpers\CustomHelper::formatDateTime($crm->date ?? $crm->created_at) }}
                            </span>
                        </div>

                    </div>

                    <div class="flex flex-col items-end justify-center border-l border-gray-100 pl-4 shrink-0 min-w-[90px]">
                        <div class="text-lg font-bold text-red-600">
                            {{ $crmTotal > 0 ? '$' . number_format($crmTotal, 2) : '—' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Damage Charge</div>
                    </div>

                </div>
            </div>
        @endforeach

    </div>

    {{-- Pagination (order_products only; CRM records are not paginated) --}}
    @if($records->hasPages())
        <div class="mt-6">
            {{ $records->links() }}
        </div>
    @endif
@endif
