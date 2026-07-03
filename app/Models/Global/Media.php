<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

use Illuminate\Support\Facades\Crypt;

// Helpers
use App\Helpers\ModelHelper;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'asset_type', // Public Asset,Secure Asset
        'folder_name',
        'file_name',
        'original_file_name',
        'file_extension',
        'file_type',
        'mime_type',
        'file_size',
        'signed_url',
        'download_signed_url',
        'is_used', // 'Yes','No'
        'model_type',
        'model_id',
        // Media Library fields
        'title',
        'alt_text',
        'caption',
        'description',
        'width',
        'height',
        'media_folder_id',
        'uploaded_by',
    ];

    protected $table = 'media';

    protected $appends = ['url'];


    // Scopes
    public function scopeOrder($query)
    {
        return $query->orderBy('id', 'DESC');
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'MED');
        });
    }

    public function getUrlAttribute(): ?string
    {
        return $this->getUrl() ?? null;
    }

    public function getUrl()
    {
        if ($this->asset_type == 'Secure Asset') {
            return  Storage::disk('secure_asset')->url($this->folder_name . '/' . $this->file_name);
        } else {
            return  Storage::disk('public_asset')->url($this->folder_name . '/' . $this->file_name);
        }
    }

    public function getFilePath(): string
    {
        return $this->folder_name . '/' . $this->file_name;
    }

    public function downloadMedia($session_id = null)
    {
        if ($this->asset_type == 'Secure Asset') {

            return \URL::temporarySignedRoute(
                'admin.download_sign_media',
                now()->addMinutes(10),
                ['unique_id' => $this->unique_id, 'session' => !empty($session_id) ? $session_id : session()->getId()]
            );
        } else {
            return route('admin.download_media', ['unique_id' => Crypt::encryptString($this->unique_id)]);
        }
    }


    public function folder()
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /** Serialized array used by the media library UI and picker API */
    public function toLibraryArray(): array
    {
        return [
            'id'                  => $this->id,
            'unique_id'           => $this->unique_id,
            'url'                 => $this->url,
            'original_file_name'  => $this->original_file_name ?? $this->file_name,
            'file_size_formatted' => $this->getFileSize(),
            'file_size'           => $this->file_size,
            'width'               => $this->width,
            'height'              => $this->height,
            'alt_text'            => $this->alt_text,
            'title'               => $this->title,
            'caption'             => $this->caption,
            'description'         => $this->description,
            'mime_type'           => $this->mime_type,
            'file_extension'      => $this->file_extension,
            'file_type'           => $this->file_type,
            'folder_id'           => $this->media_folder_id,
            'is_image'            => $this->isImage(),
            'created_at_formatted'=> $this->created_at?->diffForHumans(),
            'created_at_date'     => $this->created_at?->format('M j, Y'),
        ];
    }

    public function  getFileSize()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
