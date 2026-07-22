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
        'comment_type',
    ];

    protected $casts = [
        'comment_type' => \App\Enums\Tasks\TaskCommentType::class,
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(TaskMedia::class, 'task_comment_id');
    }
}
