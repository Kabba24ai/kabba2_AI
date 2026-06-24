<?php

namespace App\Models\Tasks;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $table = 'daily_task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
