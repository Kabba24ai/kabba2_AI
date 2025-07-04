<?php

namespace App\Http\Controllers\Front\Auth\Register;

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
        $email = strtolower(trim($request->get('email'))); 
    
        if (!$email) {
            return response()->json(false); 
        }
    
        $query = Customer::whereRaw('LOWER(email) = ?', [$email]); 
    
        $exists = $query->exists();
    
        return response()->json(['valid' => !$exists]); 

    }
    
    
}
