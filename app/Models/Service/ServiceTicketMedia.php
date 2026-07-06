<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceTicketEventType;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Media / attachments on a service ticket. The physical file lives on the
 * central media system (MediaHelper → `media` table, public_asset disk);
 * this row adds the service-specific category, notes, and a denormalized
 * snapshot so the record survives even if the central row is removed.
 */
class ServiceTicketMedia extends Model
{
    use SoftDeletes;

    protected $table = 'service_ticket_media';

    protected $fillable = [
        'service_ticket_id',
        'media_id',
        'media_type',
        'category',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'media_type' => ServiceMediaType::class,
        'category'   => ServiceMediaCategory::class,
    ];

    protected static function booted(): void
    {
        static::created(function (self $item) {
            ServiceTicketEvent::record(
                $item->service_ticket_id,
                ServiceTicketEventType::MediaUploaded,
                notes: sprintf('Media uploaded: %s (%s)', $item->original_filename ?? basename($item->file_path), $item->category->label()),
            );
        });

        static::deleted(function (self $item) {
            ServiceTicketEvent::record(
                $item->service_ticket_id,
                ServiceTicketEventType::MediaRemoved,
                notes: sprintf('Media removed: %s (%s)', $item->original_filename ?? basename($item->file_path), $item->category->label()),
            );
        });
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function mediaAsset()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->mediaAsset?->url
            ?? ($this->file_path ? Storage::disk('public_asset')->url($this->file_path) : null);
    }

    public function isImage(): bool
    {
        return $this->media_type === ServiceMediaType::Image;
    }
}
