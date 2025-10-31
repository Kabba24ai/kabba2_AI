<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use App\Models\Customers\Tag;

use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $tags = Tag::select('unique_id', 'name','id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'tags' => $tags,
        ]);
    }
}
