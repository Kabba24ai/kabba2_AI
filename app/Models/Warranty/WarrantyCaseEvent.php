<?php

namespace App\Models\Warranty;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only timeline entry for a warranty case — recorded by model
 * hooks and the queue engine, never edited or deleted.
 */
class WarrantyCaseEvent extends Model
{
    protected $fillable = [
        'warranty_case_id',
        'event_type',
        'old_value',
        'new_value',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'event_type' => WarrantyCaseEventType::class,
        'metadata'   => 'array',
    ];

    /** Single write path for timeline entries; user defaults to whoever is signed in. */
    public static function record(
        int $caseId,
        WarrantyCaseEventType $type,
        ?string $old = null,
        ?string $new = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'warranty_case_id' => $caseId,
            'event_type'       => $type,
            'old_value'        => $old,
            'new_value'        => $new,
            'user_id'          => auth()->id(),
            'notes'            => $notes,
            'metadata'         => $metadata,
        ]);
    }

    public function warrantyCase()
    {
        return $this->belongsTo(WarrantyCase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
