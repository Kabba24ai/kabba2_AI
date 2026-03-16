<?php

namespace App\Http\Resources\Api\ProfileSettings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ConfigurationHelper;

class ProfileSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [];

        /**
         * ----------------------------------
         * 1. Profile Settings
         * ----------------------------------
         */
        foreach ($this->resource as $key => $value) {
            if (!str_contains($key, '_formatted')) {
                $data[$key] = $value;
            }
        }

        /**
         * ----------------------------------
         * 2. Social Media Settings (Merge)
         * ----------------------------------
         */
        $socialSettings = ConfigurationHelper::getSettings('Social Media Settings');

        foreach ($socialSettings as $key => $value) {
            if (!str_contains($key, '_formatted')) {
                $data[$key] = $value;
            }
        }

        /**
         * ----------------------------------
         * 3. Add Logo URL
         * ----------------------------------
         */
        $data['logo_url'] = ConfigurationHelper::getBrandingLogo();



        return $data;
    }
}