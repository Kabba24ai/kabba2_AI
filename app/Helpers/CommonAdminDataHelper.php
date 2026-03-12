<?php

namespace App\Helpers;

class CommonAdminDataHelper
{
    public static function setCommonData()
    {
        $logo = \App\Helpers\ConfigurationHelper::getProfileLogo();
        $data = [
            'logo'=> $logo,
        ];

        view()->share($data);
    }
}
