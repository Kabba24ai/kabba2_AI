<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Application;
use App\Http\Resources\Api\EmploymentApplication\V1\Application\ApplicationDetailResource;

use Illuminate\Http\JsonResponse;


   class ShowController extends BaseController
    {
        public function __invoke(Application $application)
        {
            return response()->json([
                'success' => true,
                'data' => new ApplicationDetailResource($application),
                'row_data'=> $application
            ]);
        }
    }

