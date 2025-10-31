<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use App\Models\Customers\Tag;

class DeleteController extends Controller
{
    public function __invoke(Tag $tag)
    {
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully!',
        ]);
    }
}
