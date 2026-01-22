<?php

namespace App\Helpers;

use Str;

class ModelHelper
{

    public static function generateUniqueID($model, $prefix, $slot = 2, $slot_length = 4)
    {
        while (true) {
            $uniqueId = self::generateUniqueIDString($prefix, $slot, $slot_length);
            if (!$model->where('unique_id', $uniqueId)->exists()) {
                break;
            }
        }
        return $uniqueId;
    }

    private static function generateUniqueIDString($prefix, $slot = 2, $slot_length = 4)
    {
        $arr[] = $prefix;
        for ($i = 0; $i < $slot; $i++) {
            $arr[] = Str::upper(Str::random($slot_length));
        }
        return implode('-', $arr);
    }


    public static function generateNumericUniqueID($prefix, $slot = 2, $min = 1000, $max = 9999)
    {
        $arr[] = $prefix;
        for ($i = 0; $i < $slot; $i++) {
            $arr[] = rand($min, $max);
        }
        return implode('-', $arr);
    }

    // public static function singleLogin($guard_name = 'web')
    // {
    //     $model_item = auth($guard_name)->user();
    //     $current_device_id = request()->session()->getId();

    //     // Check if the user's current device matches the stored device

    //     if (auth($guard_name)->check()) {
    //         $model_item->current_device_session_id = $current_device_id;

    //         $logged_devices = json_decode($model_item->logged_in_devices, true) ?? [];
    //         foreach ($logged_devices as $key => $device) {
    //             Session::getHandler()->destroy($device); // destroy session file
    //         }
    //         // Update logged-in devices to only include the current device
    //         $model_item->logged_in_devices = json_encode([$current_device_id]);
    //         $model_item->save();
    //     }
    // }
}
