<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaRetentionClass;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceMediaWorkflowStage;
use App\Enums\Service\ServiceTicketEventType;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Media / attachments on a service ticket. Legacy rows' physical files live
 * on the central media system (MediaHelper → `media` table, public_asset
 * disk); new uploads go straight to the dedicated `service_media` disk with
 * no central `media` row (see MediaHelper::uploadServiceMediaFile()). Either
 * way this row carries a denormalized snapshot so the record survives even
 * if the central `media` row is removed. `attachable` optionally links the
 * file to the specific record it documents (e.g. a note) rather than just
 * the ticket as a whole.
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
        'workflow_stage',
        'retention_class',
        'disk',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
        'notes',
        'attachable_type',
        'attachable_id',
    ];

    protected $casts = [
        'media_type'      => ServiceMediaType::class,
        'category'        => ServiceMediaCategory::class,
        'workflow_stage'  => ServiceMediaWorkflowStage::class,
        'retention_class' => ServiceMediaRetentionClass::class,
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

    /** The specific record this file documents (e.g. a note), if any. */
    public function attachable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->mediaAsset) {
            return $this->mediaAsset->url;
        }

        return $this->file_path ? Storage::disk($this->disk ?: 'public_asset')->url($this->file_path) : null;
    }

    public function isImage(): bool
    {
        return $this->media_type === ServiceMediaType::Image;
    }
}
