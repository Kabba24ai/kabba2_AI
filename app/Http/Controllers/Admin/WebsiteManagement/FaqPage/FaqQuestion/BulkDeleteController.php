<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;

class BulkDeleteController extends Controller
{
    public function __invoke(Request $request)
    {

        // dd($request->all());

        $ids = json_decode($request->input('ids'), true);

        if (!empty($ids)) {
            FaqQuestions::whereIn('id', $ids)->delete();
        }

        flash(count($ids) . ' FAQ questions deleted successfully.')->success();

        return back();
    }
}
