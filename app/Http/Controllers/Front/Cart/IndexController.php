<?php

namespace App\Http\Controllers\Front\Cart;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use Illuminate\Http\Request;

class IndexController extends Controller
{
 
    public function get()
    {
        $taxRate = $this->getTaxRate();

        return response()->json([
            'cart' => [
                'rental_cart' => [], 
                'tax_rate' => $taxRate,
            ],
        ]);
    }

    private function getTaxRate()
    {
        $setting = Setting::where('setting_name', 'sales_tax')->first();
        return $setting ? floatval($setting->setting_value) : 0;
    }
}
