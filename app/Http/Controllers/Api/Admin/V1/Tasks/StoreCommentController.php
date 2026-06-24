<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\Tasks\StoreTaskCommentRequest;
use App\Models\Tasks\Task;

class StoreCommentController extends BaseController
{
    public function __invoke(StoreTaskCommentRequest $request, Task $task)
    {
        $task->comments()->create([
            'user_id' => auth('api_user')->id(),
            'comment' => $request->validated('comment'),
        ]);

        $task->logActivity('comment_added');

        return response()->json(['success' => true, 'message' => 'Comment added.'], 201);
    }
}
