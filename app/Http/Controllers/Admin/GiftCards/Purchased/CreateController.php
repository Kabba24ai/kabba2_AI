<?php

namespace App\Http\Controllers\Admin\GiftCards\Purchased;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\PurchaseGiftCardRequest;
use App\Models\Stores\Store;
use App\Services\GiftCards\GiftCardPermissions;

class CreateController extends Controller
{
    public function __invoke()
    {
        GiftCardPermissions::assert(auth()->user(), GiftCardPermissions::SELL);

        return view('admin.gift_cards.purchased.create', [
            'stores' => Store::query()->orderBy('store_name')->get(),
            'fundingMethods' => PurchaseGiftCardRequest::fundingMethods(),

            // Reused across retries of this one submission so a double-click
            // issues one card, not two. The service treats a seen key as
            // already-done and returns the existing card.
            'idempotencyKey' => 'gc-purchase-'.\Illuminate\Support\Str::uuid(),
        ]);
    }
}
