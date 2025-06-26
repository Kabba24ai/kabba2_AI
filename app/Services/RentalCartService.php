<?php

namespace App\Services;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\Log;

class RentalCartService
{
    protected $sessionKey = 'rental_cart';

    public function getCart()
    {
        return session()->get($this->sessionKey, []);
    }

    public function addItem(array $item)
    {
        $cart = $this->getCart();
    
        $addons = json_decode($item['addons'], true) ?? [];
        $addonsTotal = collect($addons)->sum('price');
        $item['addons'] = $addons;

        $delivery_pickup = json_decode($item['delivery_pickup'], true) ?? [];
       
        $item['delivery_pickup'] = $delivery_pickup;
        
    
        // Convert quantity to integer
        $item['qty'] = intval($item['qty']);
    
        // Get tax rate from settings
        $taxRate = $this->getTaxRate(); // e.g., 0.0975


        // Add delivery fee
        $deliveryFee = isset($item['delivery_fee']) ? floatval($item['delivery_fee']) : 0;
        $item['delivery_fee'] = $deliveryFee;
    
        //Check if same product_id exists
        $updated = false;
    
        foreach ($cart as $key => $existingItem) {
            if ($existingItem['product_id'] == $item['product_id']) {
                //Combine quantities
                $item['qty'] += intval($existingItem['qty']);
    
                //Replace other details with new entry
                $cart[$key] = $item;
                $updated = true;
                break;
            }
        }
    
        if (!$updated) {
            $cart[] = $item; // New product, add to cart
        }
    
        //Recalculate base total
        $baseTotal = ($item['base_price'] + $addonsTotal) * $item['qty'];
    
        // Set computed values
        $taxAmount = round($baseTotal * $taxRate, 2);
        $item['total_price'] = round($baseTotal, 2); // Without tax
        $item['tax_rate'] = $taxRate;
        $item['tax_amount'] = $taxAmount;
        $item['total_price_with_tax'] = round($baseTotal + $taxAmount, 2);
    
        // Re-assign with updated computed values
        if ($updated) {
            foreach ($cart as $k => $prod) {
                if ($prod['product_id'] == $item['product_id']) {
                    $cart[$k] = $item;
                    break;
                }
            }
        }
    
        session()->put($this->sessionKey, $cart);
    
        return $cart;
    }
    

    public function getTaxRate()
    {
        
        // Load the settings from the database
        $settings = Setting::where('setting_name', 'sales_tax')->first();

        // Attempt to get from DB settings
        $rate = $settings->setting_value ; 

        // Fallback to default if not set
        return $rate !== null ? $rate : 0;
    }


    public function removeItem(int $index)
    {
        $cart = $this->getCart();

        if (isset($cart[$index])) {
            unset($cart[$index]);
            $cart = array_values($cart); // Reindex array
            session()->put($this->sessionKey, $cart);
        }

        return $cart;
    }

    public function clearCart()
    {
        session()->forget($this->sessionKey);
        return [];
    }
}
