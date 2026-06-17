<?php

namespace App\Models\Dispatch;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

class DispatchAiDraft extends Model
{
    protected $table = 'dispatch_ai_drafts';

    protected $fillable = [
        'unique_id',
        'draft_date',
        'look_ahead_days',
        'ai_model',
        'prompt_tokens',
        'completion_tokens',
        'response_time_ms',
        'ai_reasoning',
        'confidence_score',
        'triggered_by',
        'triggered_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'draft_date'       => 'date',
            'confidence_score' => 'decimal:2',
        ];
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DispatchAiDraftAssignment::class, 'draft_id');
    }

    public function triggeredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $draft) {
            if (empty($draft->unique_id)) {
                $draft->unique_id = 'DAI-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }
}
