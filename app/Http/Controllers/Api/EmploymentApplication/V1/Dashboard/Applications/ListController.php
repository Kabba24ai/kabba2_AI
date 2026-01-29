<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Application;
use App\Http\Resources\Api\EmploymentApplication\V1\Application\ApplicationResource;
use Illuminate\Http\JsonResponse;

class ListController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $applications = Application::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Applications fetched successfully.',
            'data'    => ApplicationResource::collection($applications),
        ]);
    }
}
