<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;

use Illuminate\Http\JsonResponse;

class FetchSalesFunnels extends Controller
{
    public function __invoke(): JsonResponse
    {
        $funnels = SalesFunnel::select(
                'id',
                'unique_id',
                'funnel_name'
            )
            ->active()
            ->orderBy('funnel_name')
            ->get();

        return response()->json([

            'success' => true,

            'funnels' => $funnels,

        ]);
    }
}