<?php

namespace App\Http\Controllers\Admin\Crm\BillingSummary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
// Models
use App\Models\Locations\State;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User ;
class ViewController extends Controller
{
    
     /**
     * Show the form for view the specified product option.
     *
     * @param string $unique_id
     * @return 
     */
    public function __invoke($unique_id)
    {
         session()->flash('active_tab', 'credit');
    
        return redirect()->route('admin.crm.customers.view', $unique_id);    
    }
}
