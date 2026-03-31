<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;

// Models
use App\Models\Stores\Store;

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
        $objRecord = Store::where('unique_id', $unique_id)->firstOrFail();
        $primaryFlag = $objRecord->is_primary == 'Yes' ? true : false;

        if(OrderProduct::where('delivery_store_id', $objRecord->id)->exists() || OrderProduct::where('pickup_store_id', $objRecord->id)->exists()){
            flash('Cannot delete store. It is associated with existing orders.')->error();
            return redirect()->route('admin.stores.index');
        }

        $objRecord->delete();

        if($primaryFlag){
            Store::where('is_primary', 'No')->first()?->update(['is_primary' => 'Yes']);
        }

        flash('Store deleted successfully.')->success();
        return redirect()->route('admin.stores.index');
    }
}
