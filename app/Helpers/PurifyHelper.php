<?php

namespace App\Helpers;
use Stevebauman\Purify\Facades\Purify;

class PurifyHelper
{
    public static function purify($requestArr, $omit_fields = [])
    {
        $config = config('purify.configs.default');
        if (!is_null($requestArr) && is_array($requestArr) && count($requestArr) > 0) {
            $clearRequestArr = [];
            foreach ($requestArr as $key => $item) {
                if (is_array($item) && count($item) > 0) {
                    $clearRequestArr[$key] = self::purify($item, $omit_fields);
                } else if ($item != '' && $item != null) {

                    if (count($omit_fields) > 0 && in_array($key, $omit_fields)) {
                        $clearRequestArr[$key] = Purify::config($config)->clean(str_replace('&gt;', '>', str_replace('&lt;', '<', $item)),[
                            'HTML.Allowed' => 'u', // Allow the "u" tag
                        ]);
                    } else {
                        $clearRequestArr[$key] = Purify::config($config)->clean(str_replace('&gt;', '>', str_replace('&lt;', '<', $item)),[
                            'HTML.Allowed' => 'u', // Allow the "u" tag
                        ]);
                        $clearRequestArr[$key] = strip_tags($item);
                    }
                } else {
                    $clearRequestArr[$key] = null;
                }
            }
            return $clearRequestArr;
        } else if ($requestArr != '' && !is_array($requestArr)) {
            $returnval = Purify::config($config)->clean(str_replace('&gt;', '>', str_replace('&lt;', '<', $requestArr)));
            return strip_tags($returnval);
        } else {
            return null;
        }
    }
}
