<?php

namespace App\Http\Controllers\Admin\GiftCards\Granted;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCards\GrantGiftCardRequest;
use App\Models\Stores\Store;
use App\Services\GiftCards\GiftCardPermissions;

class CreateController extends Controller
{
    public function __invoke()
    {
        GiftCardPermissions::assert(auth()->user(), GiftCardPermissions::GRANT);

        return view('admin.gift_cards.granted.create', [
            'stores' => Store::query()->orderBy('store_name')->get(),
            'reasonCategories' => GrantGiftCardRequest::reasonCategories(),
            'idempotencyKey' => 'gc-grant-'.\Illuminate\Support\Str::uuid(),
        ]);
    }
}
