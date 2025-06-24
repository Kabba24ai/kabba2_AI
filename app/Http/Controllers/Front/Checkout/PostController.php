<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Request
use App\Http\Requests\Front\Checkout\PostRequest;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {
        $validated = $request->validated();

        dd($validated);
    }
}
