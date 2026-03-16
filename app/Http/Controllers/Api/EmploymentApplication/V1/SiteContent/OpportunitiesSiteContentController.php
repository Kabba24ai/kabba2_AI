<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent;

use App\Http\Controllers\Api\BaseController;
use App\Models\Configurations\OpportunitiesSiteContent;
use Illuminate\Http\JsonResponse;

class OpportunitiesSiteContentController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $contents = OpportunitiesSiteContent::where('is_active', true)
            ->orderBy('id')
            ->get([
                'id',
                'section_key',
                'title',
                'content',
                'updated_at'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'SiteContent fetched successfully.',
            'data' => $contents
        ]);
    }
}