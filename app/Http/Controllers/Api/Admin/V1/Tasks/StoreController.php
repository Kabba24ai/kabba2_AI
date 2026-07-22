<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Enums\Tasks\TaskStatus;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\Tasks\StoreTaskRequest;
use App\Models\Tasks\Task;

class StoreController extends BaseController
{
    public function __invoke(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $data['created_by_user_id'] = auth('api_user')->id();
        // Business rule: every new task starts Open. Never derived from input.
        $data['status'] = TaskStatus::Open->value;

        $task = Task::create($data);
        $task->logActivity('task_created', null, $task->title);

        return response()->json([
            'success' => true,
            'message' => 'Task created.',
            'data'    => ['id' => $task->id],
        ], 201);
    }
}
