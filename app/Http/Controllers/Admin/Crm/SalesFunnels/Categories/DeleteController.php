<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnelCategory;
use Illuminate\Http\Request;

// Models

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
        $objRecord = SalesFunnelCategory::where('unique_id', $unique_id)->firstOrFail();
        $objRecord->delete();

        flash('Category deleted successfully.')->success();
        return redirect()->route('admin.crm.sales-funnels.index');
    }
}
