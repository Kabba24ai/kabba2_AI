<?php

namespace App\Models\FieldService;

use App\Enums\FieldService\FieldTicketEventType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldServiceTicketNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'field_service_ticket_id',
        'note',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::created(function (self $note) {
            FieldServiceTicketEvent::record(
                $note->field_service_ticket_id,
                FieldTicketEventType::NoteAdded,
                notes: \Illuminate\Support\Str::limit($note->note, 120),
            );
        });
    }

    public function ticket()
    {
        return $this->belongsTo(FieldServiceTicket::class, 'field_service_ticket_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
