<?php

namespace App\Http\Controllers\Admin\Crm\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use Illuminate\Http\Request;



class CheckEmailController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $email = $request->get('value'); 
        $exceptId = $request->get('except'); 
    
        if (!$email) {
            return response()->json(false); 
        }
    
        $query = Customer::where('email', $email);
    
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
    
        $exists = $query->exists();
    
        return response()->json(!$exists); // true = valid, false = already taken
    }
    
    
}
