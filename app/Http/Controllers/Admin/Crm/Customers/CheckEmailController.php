<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

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
        $exceptId = $request->get('except');
    
       
    
        if (!$email) {
            return response()->json(false); 
        }
    
        $query = Customer::whereRaw('LOWER(email) = ?', [$email]); 
    
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
    
        $exists = $query->exists();
    
      
    
        return response()->json(['valid' => !$exists]); 

    }
    
    
}
