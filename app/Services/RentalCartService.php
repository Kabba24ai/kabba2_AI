<?php

namespace App\Services;
use App\Models\Configurations\Setting;

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
      

         // Calculate base price without tax
    $baseTotal = ($item['base_price'] + $addonsTotal) * $item['qty'];

    // Get tax rate from settings (or default to 0.0975)
    $taxRate = $this->getTaxRate(); // e.g., 0.0975

// Add delivery fee
$deliveryFee = isset($item['delivery_fee']) ? floatval($item['delivery_fee']) : 0;
$item['delivery_fee'] = $deliveryFee;


    // Calculate tax amount
    $taxAmount = round($baseTotal * $taxRate, 2);

    // Set all computed values
    $item['total_price'] = round($baseTotal, 2); // without tax
    $item['tax_rate'] = $taxRate;
    $item['tax_amount'] = $taxAmount;
    $item['total_price_with_tax'] = round($baseTotal + $taxAmount, 2);

        $cart[] = $item;
        session()->put($this->sessionKey, $cart);

        return $cart;
    }

    protected function getTaxRate()
    {
        
        // Load the settings from the database
        $settings = Setting::where('setting_type', 'sales_tax')->first();

        // Attempt to get from DB settings
        $rate = $settings->setting_value ?? 0.0975;

        // Fallback to default if not set
        return $rate !== null ? (float) $rate : 0.0975;
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
