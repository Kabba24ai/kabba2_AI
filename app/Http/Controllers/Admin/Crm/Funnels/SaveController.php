<?php

namespace App\Http\Controllers\Admin\Crm\Funnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'truck_delivery_cod_order_message' => 'required|string|max:500',
            'truck_delivery_cod_message_enabled' => 'nullable|boolean',
            'store_delivery_cod_order_message' => 'required|string|max:500',
            'store_delivery_cod_message_enabled' => 'nullable|boolean',

            'rental_delivery_day_before_truck_message' => 'required|string|max:500',
            'rental_delivery_day_before_store_message' => 'required|string|max:500',
            'rental_delivery_same_day_truck_message' => 'required|string|max:500',
            'rental_delivery_same_day_store_message' => 'required|string|max:500',
            'rental_delivery_day_before_truck_message_enabled' => 'nullable|boolean',
            'rental_delivery_day_before_store_message_enabled' => 'nullable|boolean',
            'rental_delivery_same_day_truck_message_enabled' => 'nullable|boolean',
            'rental_delivery_same_day_store_message_enabled' => 'nullable|boolean',

            'rental_return_day_before_truck_message' => 'required|string|max:500',
            'rental_return_day_before_store_message' => 'required|string|max:500',
            'rental_return_same_day_truck_message' => 'required|string|max:500',
            'rental_return_same_day_store_message' => 'required|string|max:500',
            'rental_return_day_before_truck_message_enabled' => 'nullable|boolean',
            'rental_return_day_before_store_message_enabled' => 'nullable|boolean',
            'rental_return_same_day_truck_message_enabled' => 'nullable|boolean',
            'rental_return_same_day_store_message_enabled' => 'nullable|boolean',
        ]);

        $settings = [
            'truck_delivery_cod_order_message' => $request->input('truck_delivery_cod_order_message'),
            'truck_delivery_cod_message_enabled' => $request->input('truck_delivery_cod_message_enabled') ? '1' : '0',
            'store_delivery_cod_order_message' => $request->input('store_delivery_cod_order_message'),
            'store_delivery_cod_message_enabled' => $request->input('store_delivery_cod_message_enabled') ? '1' : '0',

            'rental_delivery_day_before_truck_message' => $request->input('rental_delivery_day_before_truck_message'),
            'rental_delivery_day_before_store_message' => $request->input('rental_delivery_day_before_store_message'),
            'rental_delivery_same_day_truck_message' => $request->input('rental_delivery_same_day_truck_message'),
            'rental_delivery_same_day_store_message' => $request->input('rental_delivery_same_day_store_message'),
            'rental_delivery_day_before_truck_message_enabled' => $request->input('rental_delivery_day_before_truck_message_enabled') ? '1' : '0',
            'rental_delivery_day_before_store_message_enabled' => $request->input('rental_delivery_day_before_store_message_enabled') ? '1' : '0',
            'rental_delivery_same_day_truck_message_enabled' => $request->input('rental_delivery_same_day_truck_message_enabled') ? '1' : '0',
            'rental_delivery_same_day_store_message_enabled' => $request->input('rental_delivery_same_day_store_message_enabled') ? '1' : '0',

            'rental_return_day_before_truck_message' => $request->input('rental_return_day_before_truck_message'),
            'rental_return_day_before_store_message' => $request->input('rental_return_day_before_store_message'),
            'rental_return_same_day_truck_message' => $request->input('rental_return_same_day_truck_message'),
            'rental_return_same_day_store_message' => $request->input('rental_return_same_day_store_message'),
            'rental_return_day_before_truck_message_enabled' => $request->input('rental_return_day_before_truck_message_enabled') ? '1' : '0',
            'rental_return_day_before_store_message_enabled' => $request->input('rental_return_day_before_store_message_enabled') ? '1' : '0',
            'rental_return_same_day_truck_message_enabled' => $request->input('rental_return_same_day_truck_message_enabled') ? '1' : '0',
            'rental_return_same_day_store_message_enabled' => $request->input('rental_return_same_day_store_message_enabled') ? '1' : '0',
        ];

        Setting::where('setting_type', 'Default Sales Funnel Settings')
            ->whereIn('setting_name', array_keys($settings))
            ->get()
            ->each(function ($setting) use ($settings) {
                if (isset($settings[$setting->setting_name])) {
                    $setting->setting_value = $settings[$setting->setting_name];
                    $setting->save();
                }
            });

        return redirect()->route('admin.crm.funnels.index')->with('success', 'Funnel settings updated successfully.');
    }
}
