@php
    use App\Services\GiftCards\GiftCardPermissions;

    $user = auth()->user();

    // A tab is hidden when the operator could not use it. The service asserts
    // the same permission again — hiding a tab is a courtesy, not a control.
    $tabs = array_filter([
        ['label' => 'Overview',      'route' => 'admin.gift-cards.overview',         'on' => Route::is('admin.gift-cards.overview'),  'show' => true],
        ['label' => 'All Gift Cards','route' => 'admin.gift-cards.index',            'on' => Route::is('admin.gift-cards.index') || Route::is('admin.gift-cards.show'), 'show' => true],
        ['label' => 'Sell a Card',   'route' => 'admin.gift-cards.purchased.create', 'on' => Route::is('admin.gift-cards.purchased.*'),'show' => GiftCardPermissions::canSell($user)],
        ['label' => 'Grant a Card',  'route' => 'admin.gift-cards.granted.create',   'on' => Route::is('admin.gift-cards.granted.*'),  'show' => GiftCardPermissions::canGrant($user)],
        ['label' => 'Transactions',  'route' => 'admin.gift-cards.transactions',     'on' => Route::is('admin.gift-cards.transactions'),'show' => true],
        ['label' => 'Reporting',     'route' => 'admin.gift-cards.reporting',        'on' => Route::is('admin.gift-cards.reporting'),  'show' => GiftCardPermissions::canViewReports($user)],
    ], fn ($tab) => $tab['show']);
@endphp

<nav class="gc-tabs">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="gc-tab {{ $tab['on'] ? 'gc-tab-on' : '' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
