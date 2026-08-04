<?php

namespace App\Http\Controllers\Admin\GiftCards;

use App\Enums\GiftCards\GiftCardIssuanceClass;
use App\Enums\GiftCards\GiftCardStatus;
use App\Http\Controllers\Controller;
use App\Services\GiftCards\GiftCardWorkspaceQuery;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private GiftCardWorkspaceQuery $query) {}

    public function __invoke(Request $request)
    {
        $filters = [
            'class' => $request->input('class') ?: null,
            'status' => $request->input('status') ?: null,
            'search' => $request->input('search') ?: null,
        ];

        return view('admin.gift_cards.index', [
            'filters' => $filters,
            'cards' => $this->query->cards($filters),
            'classes' => GiftCardIssuanceClass::cases(),
            'statuses' => GiftCardStatus::cases(),
        ]);
    }
}
