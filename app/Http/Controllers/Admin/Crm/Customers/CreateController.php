<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Models
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;


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
    $admins = User::get();

    $states = State::get();

    $website_protocol = 'https://';       // default protocol
    $website_extension = '.com';          // default extension
    $company_website = '';
        $employees = User::orderBy('first_name')->get();

        return view('admin.crm.customers.create', compact('admins','states','company_website','website_extension','website_protocol', 'employees'));
}

}
