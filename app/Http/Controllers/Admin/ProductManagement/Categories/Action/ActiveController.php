<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Event
use App\Events\Admin\ProductManagement\Categories\Action\ActiveEvent;

// Models
use App\Models\ProductManagement\ProductCategory;

class ActiveController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke(Request $request)
    {

        if(!\PermissionUtility::checkPermission('product_categories.edit')){
            return redirect()->route('admin.dashboard.index');
        }

        $user_item = Auth::user();
        if ($request->has('selected_ids') && is_array($request->get('selected_ids')) && count($request->get('selected_ids')) > 0) {
            $product_category_list = ProductCategory::whereIn('unique_id', $request->get('selected_ids'))->get();
            if (!is_null($product_category_list)) {
                $changesArr = [];
                $item_list = [];
                foreach ($product_category_list as $product_category_item) {
                    $product_category_item->status = 'Active';
                    $changes = \ModelDataState::getDataItemChangesWithTitle($product_category_item, $product_category_item->title);
                    if (!is_null($changes)) {
                        $changesArr[] = $changes;
                        $item_list[] = $product_category_item->id;
                    }
                    $product_category_item->save();
                }
                // Call Event
                if (is_array($changesArr) && count($changesArr) > 0) {
                    ActiveEvent::dispatch([
                        'user_item' => $user_item,
                        'item_list' => $item_list,
                        'changes' => $changesArr
                    ]);
                }
                flash('Selected items successfully activated.')->success();
                return response([], 200);
            }
        }
    }
}
