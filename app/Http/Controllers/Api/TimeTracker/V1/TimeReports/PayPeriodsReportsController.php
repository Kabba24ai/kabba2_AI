<?php

    namespace App\Http\Controllers\Api\TimeTracker\V1\TimeReports;

    use App\Http\Controllers\Api\BaseController;
    use App\Models\Iam\Personnel\User;
    use Illuminate\Http\JsonResponse;
    use App\Helpers\PayPeriodHelper;
    use Illuminate\Http\Request;
    use Carbon\Carbon;

    class PayPeriodsReportsController extends BaseController
    {
        public function __invoke(Request $request): JsonResponse
        {
             return response()->json([
                'success' => true,
                'data' => PayPeriodHelper::listPeriods(12),
            ]);
        }
    }
