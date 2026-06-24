<?php

namespace App\Models\Tasks;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

class TaskActivityLog extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
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
