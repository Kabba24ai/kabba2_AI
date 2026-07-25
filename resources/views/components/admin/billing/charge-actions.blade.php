@props([
    // Action keys this row is entitled to, decided by the CALLER from charge
    // state, permissions, and business rules — never from which page is
    // rendering (approved architecture rule). The component renders whatever
    // subset it is given, always in the one canonical order below.
    'actions' => [],
])

@php
    // Billing Charge Operations Commonization — THE canonical action
    // vocabulary. One icon, one color, one tooltip, one order for every
    // surface (Fuel Workspace, Order Details Billing Engine, future Damage
    // Workspace). Change it here or nowhere.
    //
    // Hover classes are verified present in the compiled Tailwind bundle —
    // production deploys do NOT rebuild assets, so never add a class here
    // without confirming it exists in public/build/assets/app-*.css or
    // shipping a rebuilt bundle with the change.
    $chargeActionDefs = [
        'history'       => ['title' => 'Charge History',      'hover' => 'hover:text-blue-600 hover:bg-blue-50'],
        'notes'         => ['title' => 'Notes',               'hover' => 'hover:text-yellow-600 hover:bg-yellow-50'],
        'adjust'         => ['title' => 'Adjust Amount',       'hover' => 'hover:text-purple-600 hover:bg-purple-50'],
        'payment'        => ['title' => 'Collect Payment',     'hover' => 'hover:text-green-600 hover:bg-green-50'],
        'add-to-account' => ['title' => 'Add to Account',      'hover' => 'hover:text-indigo-600 hover:bg-indigo-50'],
        'refund'         => ['title' => 'Refund',              'hover' => 'hover:text-amber-600 hover:bg-amber-50'],
        'view-damage'   => ['title' => 'View Damage Details', 'hover' => 'hover:text-indigo-600 hover:bg-indigo-50'],
        'resolve'       => ['title' => 'Resolve',             'hover' => 'hover:text-emerald-600 hover:bg-emerald-50'],
        'uncollectible' => ['title' => 'Mark Uncollectible',  'hover' => 'hover:text-red-600 hover:bg-red-50'],
        'delete'        => ['title' => 'Delete',              'hover' => 'hover:text-red-600 hover:bg-red-50'],
    ];

    // Canonical order (approved): History · Notes · Adjust · Collect Payment
    // · Refund · [View Damage] · Resolve · Uncollectible · [Delete].
    $ordered = array_values(array_intersect(array_keys($chargeActionDefs), (array) $actions));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
    @foreach ($ordered as $key)
        @php $def = $chargeActionDefs[$key]; @endphp
        <button type="button" data-action="{{ $key }}" title="{{ $def['title'] }}" aria-label="{{ $def['title'] }}"
                class="p-2 rounded-lg border border-gray-200 text-gray-500 {{ $def['hover'] }} transition">
            @switch($key)
                @case('history')       <x-heroicon-o-clock class="w-4 h-4" /> @break
                @case('notes')         <x-heroicon-o-chat-bubble-left class="w-4 h-4" /> @break
                @case('adjust')        <x-heroicon-o-pencil-square class="w-4 h-4" /> @break
                @case('payment')       <x-heroicon-o-credit-card class="w-4 h-4" /> @break
                @case('add-to-account') <x-heroicon-o-building-library class="w-4 h-4" /> @break
                @case('refund')        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" /> @break
                @case('view-damage')   <x-heroicon-o-eye class="w-4 h-4" /> @break
                @case('resolve')       <x-heroicon-o-check-circle class="w-4 h-4" /> @break
                @case('uncollectible') <x-heroicon-o-no-symbol class="w-4 h-4" /> @break
                @case('delete')        <x-heroicon-o-trash class="w-4 h-4" /> @break
            @endswitch
        </button>
    @endforeach
</div>
