<?php

namespace App\Helpers;

class CommonAdminDataHelper
{
    public static function setCommonData()
    {
        $logo = \App\Helpers\ConfigurationHelper::getBrandingLogo();
        $data = [
            'logo'=> $logo,
        ];

        view()->share($data);
    }
}
