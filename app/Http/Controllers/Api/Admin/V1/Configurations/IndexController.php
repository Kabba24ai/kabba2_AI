<?php

namespace App\Http\Controllers\Api\Admin\V1\Configurations;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Model
use App\Models\Configurations\Setting;

class IndexController extends BaseController
{
    /**
     * Configurations List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(): JsonResponse
    {
        $settings = Setting::whereIn('setting_type', ['Price Settings'])
            ->select('id','unique_id','setting_name','setting_title','setting_value','placeholder','setting_options', 'setting_type')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('setting_type'); // keys are strings

        if ($settings->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.configurations.no_configurations_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.configurations.configurations_found'),
            'configurations' => $settings,
        ]);
    }
}
