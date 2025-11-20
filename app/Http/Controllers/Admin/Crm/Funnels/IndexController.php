<?php

namespace App\Http\Controllers\Admin\Crm\Funnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use App\Helpers\ConfigurationHelper;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $defaultFunnels = ConfigurationHelper::getSettings('Default Sales Funnel Settings');

        return view('admin.crm.funnels.index', compact('defaultFunnels'));
    }
}
