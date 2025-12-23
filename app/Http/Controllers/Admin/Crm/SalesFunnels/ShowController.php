<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;

class ShowController extends Controller
{
    public function __invoke(string $unique_id)
    {
        $funnel = SalesFunnel::with('category')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $funnel,
        ]);
    }
}
