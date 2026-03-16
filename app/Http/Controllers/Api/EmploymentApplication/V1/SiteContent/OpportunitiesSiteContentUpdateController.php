<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent;

use App\Http\Controllers\Api\BaseController;
use App\Models\Configurations\OpportunitiesSiteContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OpportunitiesSiteContentUpdateController extends BaseController
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        $content = OpportunitiesSiteContent::findOrFail($id);

        $content->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Content updated successfully.',
            'data' => $content
        ]);
    }
}