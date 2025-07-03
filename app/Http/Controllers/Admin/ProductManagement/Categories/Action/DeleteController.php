<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Event
use App\Events\Admin\ProductManagement\Categories\Action\DeleteEvent;

// Models
use App\Models\ProductManagement\ProductCategory;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke(Request $request)
    {

        if (!\PermissionUtility::checkPermission('product_categories.delete')) {
            return redirect()->route('admin.dashboard.index');
        }

        $user_item = Auth::user();

        $changesArr = [];
        if ($request->has('selected_ids') && is_array($request->get('selected_ids')) && count($request->get('selected_ids')) > 0) {
            $product_category_list = ProductCategory::whereIn('unique_id', $request->get('selected_ids'))->get();
            if (!is_null($product_category_list)) {
                foreach ($product_category_list as $product_category_item) {
                    $changesArr[]['field_title'] = $product_category_item->title;
                    $product_category_item->delete();
                }
                // Call Event
                if (is_array($changesArr) && count($changesArr) > 0) {
                    DeleteEvent::dispatch([
                        'user_item' => $user_item,
                        'changes' => $changesArr
                    ]);
                }
                flash('Selected items successfully deleted.')->success();
                return response([], 200);
            }
        }
    }
}
