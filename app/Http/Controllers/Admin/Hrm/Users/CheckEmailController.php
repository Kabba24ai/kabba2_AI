<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;

// App\Models\Iam\Personnel;

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
    
        $query = User::whereRaw('LOWER(email) = ?', [$email]); 
    
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
    
        $exists = $query->exists();

    
        return response()->json(['valid' => !$exists]); 

    }
    
    
}
