<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;



class CreateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
       
        return view('admin.crm.customer_portal.create');
    }
    
}
